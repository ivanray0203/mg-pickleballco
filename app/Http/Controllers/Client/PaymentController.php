<?php

namespace App\Http\Controllers\Client;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    /**
     * List the signed-in customer's own payment attempts.
     */
    public function index(Request $request): Response
    {
        $payments = $request->user()->payments()
            ->with(['booking.court', 'booking.user'])
            ->orderByDesc('payments.created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('client/payments', [
            'payments' => PaymentResource::collection($payments),
        ]);
    }

    /**
     * Show a single payment's customer-safe details (owner only).
     */
    public function detail(Payment $payment): Response
    {
        $this->authorize('view', $payment);

        $payment->load(['booking.court', 'booking.user']);

        return Inertia::render('client/payment-detail', [
            'payment' => new PaymentResource($payment),
        ]);
    }

    /**
     * Show the GCash checkout for a pending booking (owner only).
     */
    public function checkout(Booking $booking): Response|RedirectResponse
    {
        $this->authorize('view', $booking);

        if ($booking->status === BookingStatus::Confirmed) {
            return to_route('bookings.payment.success', $booking);
        }

        // A cancelled/expired/completed booking or a lapsed hold can't be paid.
        if ($booking->status !== BookingStatus::PendingPayment || $booking->isExpired()) {
            return to_route('bookings.show', $booking);
        }

        $booking->load(['court', 'user']);

        return Inertia::render('client/checkout', [
            'booking' => new BookingResource($booking),
            'server_now' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Create/resume a GCash checkout and redirect the customer to the provider.
     *
     * Amount is snapshotted server-side from the booking; nothing about the
     * price is trusted from the browser. Repeated clicks reuse the in-flight
     * attempt rather than creating duplicates.
     */
    public function pay(Booking $booking): SymfonyResponse
    {
        $this->authorize('view', $booking);

        if ($booking->status !== BookingStatus::PendingPayment || $booking->isExpired()) {
            return to_route('bookings.show', $booking)
                ->with('error', 'This booking can no longer be paid.');
        }

        $payment = $this->payments->startCheckout($booking);

        // External, full-page redirect to the hosted GCash checkout.
        return Inertia::location($payment->checkout_url);
    }

    /**
     * Post-payment landing page. Reflects the authoritative booking status —
     * it never confirms a booking itself; only the verified webhook does.
     */
    public function success(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['court', 'user']);

        return Inertia::render('client/payment-success', [
            'booking' => new BookingResource($booking),
            'confirmed' => $booking->status === BookingStatus::Confirmed,
        ]);
    }

    /**
     * Landing page when the customer cancels or abandons checkout.
     */
    public function cancel(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['court', 'user']);

        return Inertia::render('client/payment-cancelled', [
            'booking' => new BookingResource($booking),
            'can_retry' => $booking->status === BookingStatus::PendingPayment && ! $booking->isExpired(),
        ]);
    }
}
