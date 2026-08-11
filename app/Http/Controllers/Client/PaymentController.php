<?php

namespace App\Http\Controllers\Client;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'embed_checkout' => $this->embedsCheckout(),
        ]);
    }

    /**
     * Create/resume a GCash checkout and send the customer to the provider.
     *
     * Amount is snapshotted server-side from the booking; nothing about the
     * price is trusted from the browser. Repeated clicks reuse the in-flight
     * attempt rather than creating duplicates.
     *
     * When the page is embedding the hosted checkout it asks for the URL as
     * JSON and frames it; otherwise this is a full-page redirect as before.
     */
    public function pay(Request $request, Booking $booking): SymfonyResponse
    {
        $this->authorize('view', $booking);

        $wantsUrl = $this->embedsCheckout() && $request->boolean('embedded');

        if ($booking->status !== BookingStatus::PendingPayment || $booking->isExpired()) {
            if ($wantsUrl) {
                return response()->json([
                    'message' => 'This booking can no longer be paid.',
                ], SymfonyResponse::HTTP_CONFLICT);
            }

            return to_route('bookings.show', $booking)
                ->with('error', 'This booking can no longer be paid.');
        }

        $payment = $this->payments->startCheckout($booking);

        if ($wantsUrl) {
            return response()->json(['checkout_url' => $payment->checkout_url]);
        }

        // External, full-page redirect to the hosted GCash checkout.
        return Inertia::location($payment->checkout_url);
    }

    /**
     * Whether the hosted checkout should be framed on-site rather than
     * redirected to. Requires PayMongo to have allowlisted this domain.
     */
    private function embedsCheckout(): bool
    {
        return (bool) config('services.paymongo.embed_checkout');
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
