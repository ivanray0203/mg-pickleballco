<?php

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Models\Payment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayMongo implementation of the {@see PaymentGateway} contract. GCash is
 * offered as a payment method on a hosted checkout session.
 *
 * All credentials come from config/services.php (env-backed); nothing is
 * hardcoded. HTTP is performed with Laravel's client so it is fully fakeable
 * in tests.
 *
 * @see https://developers.paymongo.com/reference/checkout-session-resource
 */
class PayMongoGateway implements PaymentGateway
{
    private const BASE_URL = 'https://api.paymongo.com/v1';

    /**
     * @param  array{secret_key: ?string, webhook_secret: ?string, success_url: ?string, cancel_url: ?string}  $config
     */
    public function __construct(private array $config) {}

    public function provider(): PaymentProvider
    {
        return PaymentProvider::PayMongo;
    }

    public function createCheckout(Payment $payment): CheckoutResult
    {
        $descriptor = $this->checkoutDescriptor($payment);

        $response = Http::withBasicAuth($this->secretKey(), '')
            ->acceptJson()
            ->asJson()
            ->post(self::BASE_URL.'/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'payment_method_types' => ['gcash'],
                        'line_items' => [[
                            'name' => $descriptor['name'],
                            'currency' => 'PHP',
                            // PayMongo expects integer centavos — matches our snapshot.
                            'amount' => $payment->amount,
                            'quantity' => 1,
                        ]],
                        'reference_number' => $payment->payment_reference,
                        // Redirect targets; the landing page never confirms on
                        // its own — the webhook is authoritative.
                        'success_url' => $descriptor['success_url'],
                        'cancel_url' => $descriptor['cancel_url'],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('PayMongo checkout creation failed: '.$response->status());
        }

        $id = $response->json('data.id');
        $checkoutUrl = $response->json('data.attributes.checkout_url');

        if (! is_string($id) || ! is_string($checkoutUrl)) {
            throw new RuntimeException('PayMongo checkout response was malformed.');
        }

        return new CheckoutResult($id, $checkoutUrl);
    }

    /**
     * Build the line-item name and redirect URLs for a payment, whether it
     * belongs to a court booking or an open-play registration.
     *
     * @return array{name: string, success_url: string, cancel_url: string}
     */
    private function checkoutDescriptor(Payment $payment): array
    {
        if ($payment->booking_id !== null) {
            $booking = $payment->booking;

            return [
                'name' => 'Court booking '.$booking->booking_reference,
                'success_url' => route('bookings.payment.success', $booking),
                'cancel_url' => route('bookings.payment.cancel', $booking),
            ];
        }

        $session = $payment->openPlayRegistration->session;

        return [
            'name' => 'Open play — '.$session->title,
            'success_url' => route('open-play.show', $session),
            'cancel_url' => route('open-play.show', $session),
        ];
    }

    public function verifyWebhook(string $payload, string $signatureHeader): bool
    {
        $secret = $this->config['webhook_secret'] ?? null;

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $parts = $this->parseSignatureHeader($signatureHeader);
        $timestamp = $parts['t'] ?? null;
        // te = test-mode signature, li = live-mode signature.
        $providedSignature = $parts['te'] ?? $parts['li'] ?? null;

        if ($timestamp === null || $providedSignature === null) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($expected, $providedSignature);
    }

    public function parseWebhook(array $payload): ?WebhookEvent
    {
        $eventId = Arr::get($payload, 'data.id');
        $type = Arr::get($payload, 'data.attributes.type');

        if (! is_string($eventId) || ! is_string($type)) {
            return null;
        }

        $normalized = match ($type) {
            'checkout_session.payment.paid', 'payment.paid' => 'paid',
            'payment.failed' => 'failed',
            'checkout_session.expired' => 'expired',
            default => null,
        };

        if ($normalized === null) {
            return null;
        }

        $resource = Arr::get($payload, 'data.attributes.data', []);
        $providerReference = Arr::get($resource, 'id');
        $amount = Arr::get($resource, 'attributes.payments.0.attributes.amount')
            ?? Arr::get($resource, 'attributes.amount');

        return new WebhookEvent(
            id: $eventId,
            type: $normalized,
            providerReference: is_string($providerReference) ? $providerReference : null,
            amount: is_int($amount) ? $amount : null,
            metadata: ['event_type' => $type],
        );
    }

    public function retrievePayment(string $providerReference): array
    {
        $response = Http::withBasicAuth($this->secretKey(), '')
            ->acceptJson()
            ->get(self::BASE_URL.'/checkout_sessions/'.$providerReference);

        if ($response->failed()) {
            throw new RuntimeException('PayMongo retrieve failed: '.$response->status());
        }

        return $response->json() ?? [];
    }

    private function secretKey(): string
    {
        $key = $this->config['secret_key'] ?? null;

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('PayMongo secret key is not configured.');
        }

        return $key;
    }

    /**
     * Parse a "t=...,te=...,li=..." signature header into its parts.
     *
     * @return array<string, string>
     */
    private function parseSignatureHeader(string $header): array
    {
        $parts = [];

        foreach (explode(',', $header) as $segment) {
            $pair = explode('=', trim($segment), 2);

            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        return $parts;
    }

    /**
     * Convenience for building a valid signature header. Kept here so tests
     * and local tooling reproduce the exact scheme used for verification.
     */
    public static function signature(string $payload, string $secret, int $timestamp): string
    {
        $sig = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return "t={$timestamp},te={$sig}";
    }
}
