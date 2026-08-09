<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Booking;
use App\Models\OpenPlayRegistration;
use App\Models\Payment;
use App\Notifications\BookingConfirmed;
use App\Notifications\OpenPlayRegistrationConfirmed;
use App\Notifications\PaymentFailed;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\WebhookEvent;
use App\Settings\AppSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Owns the payment lifecycle: creating provider checkouts for bookings and
 * applying verified webhook events. Booking confirmation happens here and only
 * in response to a verified, successful payment — never from a browser redirect.
 */
class PaymentService
{
    public function __construct(
        private PaymentGateway $gateway,
        private AppSettings $settings,
    ) {}

    /**
     * Start a new payment attempt for a booking and hand back the persisted
     * Payment with its provider checkout URL. A booking can have many attempts
     * (retries after failure/expiry), so this always creates a fresh record.
     */
    public function initiate(Booking $booking, PaymentMethod $method = PaymentMethod::GCash): Payment
    {
        if ($booking->status !== BookingStatus::PendingPayment) {
            throw ValidationException::withMessages([
                'booking' => 'Only bookings awaiting payment can be paid.',
            ]);
        }

        return $this->openCheckout(['booking_id' => $booking->id], $booking->total_amount, $method);
    }

    /**
     * Start (or resume) checkout for a booking. Repeated clicks reuse an
     * existing in-flight pending attempt rather than creating duplicate
     * payments and gateway sessions.
     */
    public function startCheckout(Booking $booking, PaymentMethod $method = PaymentMethod::GCash): Payment
    {
        $existing = $booking->payments()
            ->where('status', PaymentStatus::Pending)
            ->whereNotNull('checkout_url')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->latest()
            ->first();

        return $existing ?? $this->initiate($booking, $method);
    }

    /**
     * Start (or resume) checkout for a paid open-play registration. Mirrors the
     * booking flow: repeated clicks reuse the in-flight pending attempt.
     */
    public function startCheckoutForRegistration(OpenPlayRegistration $registration, PaymentMethod $method = PaymentMethod::GCash): Payment
    {
        $existing = Payment::query()
            ->where('open_play_registration_id', $registration->id)
            ->where('status', PaymentStatus::Pending)
            ->whereNotNull('checkout_url')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->latest()
            ->first();

        return $existing ?? $this->initiateForRegistration($registration, $method);
    }

    /**
     * Open a fresh payment attempt for a registration, snapshotting the
     * session's price. Links the attempt back onto the registration.
     */
    public function initiateForRegistration(OpenPlayRegistration $registration, PaymentMethod $method = PaymentMethod::GCash): Payment
    {
        if ($registration->status !== RegistrationStatus::Reserved) {
            throw ValidationException::withMessages([
                'registration' => 'Only reserved registrations can be paid.',
            ]);
        }

        $payment = $this->openCheckout(
            ['open_play_registration_id' => $registration->id],
            $registration->session->price_amount,
            $method,
        );

        $registration->update(['payment_id' => $payment->id]);

        return $payment;
    }

    /**
     * Create a pending payment for a target (booking or registration) and open
     * a provider checkout session for it, snapshotting the amount.
     *
     * @param  array<string, int>  $target
     */
    private function openCheckout(array $target, int $amount, PaymentMethod $method): Payment
    {
        return DB::transaction(function () use ($target, $amount, $method) {
            $payment = Payment::create([
                'payment_reference' => $this->generateReference(),
                ...$target,
                'provider' => $this->gateway->provider(),
                'payment_method' => $method,
                // Snapshot the amount owed; never recomputed from live pricing.
                'amount' => $amount,
                'status' => PaymentStatus::Pending,
            ]);

            $checkout = $this->gateway->createCheckout($payment);

            $payment->update([
                'provider_reference' => $checkout->providerReference,
                'checkout_url' => $checkout->checkoutUrl,
                // Prefer the provider's expiry; otherwise apply the configured hold.
                'expires_at' => $checkout->expiresAt
                    ?? Carbon::now()->addMinutes($this->settings->bookingHoldMinutes()),
            ]);

            return $payment;
        });
    }

    /**
     * Apply a verified webhook event. The caller MUST have already verified the
     * provider signature. Processing is idempotent and transactional: duplicate
     * events are ignored, and the related booking is confirmed only for a
     * successful payment whose amount matches the snapshot.
     */
    public function processWebhook(WebhookEvent $event): void
    {
        if ($event->providerReference === null) {
            Log::warning('Payment webhook missing provider reference.', ['event' => $event->id]);

            return;
        }

        // Apply the state change transactionally, then notify the customer
        // outside the transaction (after commit) so no notification fires for
        // a change that later rolls back.
        $outcome = DB::transaction(function () use ($event) {
            $payment = Payment::query()
                ->where('provider_reference', $event->providerReference)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                Log::warning('Payment webhook for unknown reference.', [
                    'event' => $event->id,
                    'provider_reference' => $event->providerReference,
                ]);

                return null;
            }

            // Idempotency: a settled payment never transitions again, so repeat
            // or out-of-order deliveries are safely no-ops.
            if ($payment->status->isTerminal()) {
                return null;
            }

            if ($event->isPaid()) {
                return $this->applySuccessfulPayment($payment, $event);
            }

            if ($event->isFailed()) {
                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'failed_at' => Carbon::now(),
                    'metadata' => $this->mergeMetadata($payment, $event),
                ]);

                $this->releaseRegistration($payment);

                return ['type' => 'failed', 'payment' => $payment];
            }

            if ($event->isExpired()) {
                $payment->update([
                    'status' => PaymentStatus::Expired,
                    'metadata' => $this->mergeMetadata($payment, $event),
                ]);

                $this->releaseRegistration($payment);

                return ['type' => 'expired', 'payment' => $payment];
            }

            return null;
        });

        if ($outcome !== null) {
            $this->notifyCustomer($outcome['type'], $outcome['payment']);
        }
    }

    /**
     * Mark a payment paid and confirm its booking. Refuses to confirm when the
     * webhook amount disagrees with the snapshot, guarding against tampering.
     *
     * @return array{type: string, payment: Payment}|null
     */
    private function applySuccessfulPayment(Payment $payment, WebhookEvent $event): ?array
    {
        if ($event->amount !== null && $event->amount !== $payment->amount) {
            Log::warning('Payment webhook amount mismatch; not confirming.', [
                'event' => $event->id,
                'payment' => $payment->id,
                'expected' => $payment->amount,
                'received' => $event->amount,
            ]);

            return null;
        }

        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => Carbon::now(),
            'metadata' => $this->mergeMetadata($payment, $event),
        ]);

        if ($payment->booking_id !== null) {
            $booking = $payment->booking;

            if ($booking->status === BookingStatus::PendingPayment) {
                $booking->update([
                    'status' => BookingStatus::Confirmed,
                    'confirmed_at' => Carbon::now(),
                ]);
            }
        } elseif ($payment->open_play_registration_id !== null) {
            $registration = $payment->openPlayRegistration;

            if ($registration->status === RegistrationStatus::Reserved) {
                $registration->update([
                    'status' => RegistrationStatus::Confirmed,
                    'expires_at' => null,
                ]);
            }
        }

        return ['type' => 'paid', 'payment' => $payment];
    }

    /**
     * Release a paid registration whose payment failed or expired, freeing its
     * slot. No-op for booking payments.
     */
    private function releaseRegistration(Payment $payment): void
    {
        if ($payment->open_play_registration_id === null) {
            return;
        }

        $registration = $payment->openPlayRegistration;

        if ($registration !== null && $registration->status === RegistrationStatus::Reserved) {
            $registration->update([
                'status' => RegistrationStatus::Cancelled,
                'cancelled_at' => Carbon::now(),
                'expires_at' => null,
            ]);
        }
    }

    /**
     * Notify the customer of a settled payment outcome, dispatched after the
     * state change commits. Booking and open-play registration payments each
     * notify their own customer.
     */
    private function notifyCustomer(string $type, Payment $payment): void
    {
        if ($payment->booking_id !== null) {
            $user = $payment->booking->user;

            match ($type) {
                'paid' => $user->notify(new BookingConfirmed($payment->booking)),
                'failed' => $user->notify(new PaymentFailed($payment->booking, 'failed')),
                'expired' => $user->notify(new PaymentFailed($payment->booking, 'expired')),
                default => null,
            };

            return;
        }

        if ($payment->open_play_registration_id !== null && $type === 'paid') {
            $registration = $payment->openPlayRegistration;

            $registration?->user->notify(new OpenPlayRegistrationConfirmed($registration));
        }
    }

    /**
     * Record a cash payment taken over the counter for a walk-in booking. The
     * payment is written as already-paid and the booking is confirmed on the
     * spot — no provider checkout is involved. The customer is notified after
     * commit (email only if they provided an address).
     */
    public function recordCashPayment(Booking $booking): Payment
    {
        $payment = DB::transaction(function () use ($booking) {
            $payment = Payment::create([
                'payment_reference' => $this->generateReference(),
                'booking_id' => $booking->id,
                'provider' => PaymentProvider::Counter,
                'payment_method' => PaymentMethod::Cash,
                'amount' => $booking->total_amount,
                'status' => PaymentStatus::Paid,
                'paid_at' => Carbon::now(),
            ]);

            if ($booking->status === BookingStatus::PendingPayment) {
                $booking->update([
                    'status' => BookingStatus::Confirmed,
                    'confirmed_at' => Carbon::now(),
                ]);
            }

            return $payment;
        });

        // Notify after commit so nothing fires for a change that rolls back.
        $booking->refresh()->user->notify(new BookingConfirmed($booking));

        return $payment;
    }

    /**
     * Generate a unique, human-friendly payment reference (PAY-YYYYMMDD-XXXX).
     */
    public function generateReference(): string
    {
        do {
            $reference = sprintf('PAY-%s-%s', Carbon::now()->format('Ymd'), Str::upper(Str::random(4)));
        } while (Payment::query()->where('payment_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Record the processed event id alongside any prior metadata for auditing.
     *
     * @return array<string, mixed>
     */
    private function mergeMetadata(Payment $payment, WebhookEvent $event): array
    {
        $existing = $payment->metadata ?? [];
        $processed = $existing['processed_events'] ?? [];
        $processed[] = $event->id;

        return array_merge($existing, $event->metadata, [
            'processed_events' => array_values(array_unique($processed)),
        ]);
    }
}
