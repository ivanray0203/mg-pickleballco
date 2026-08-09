<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates counter (walk-in) bookings created by an admin on behalf of a
 * customer who is physically present. It resolves the customer (an existing
 * account or a freshly created guest), reserves the slot, and settles payment:
 * cash is recorded as paid immediately, while GCash opens a provider checkout
 * for the customer to pay before the hold lapses.
 */
class WalkInBookingService
{
    public function __construct(
        private BookingService $bookings,
        private PaymentService $payments,
    ) {}

    /**
     * Create a walk-in booking and settle (or begin settling) its payment.
     *
     * @param  array{
     *     customer: array{mode: string, user_id?: int|null, name?: string|null, phone?: string|null, email?: string|null},
     *     court_id: int,
     *     booking_date: string,
     *     start_time: string,
     *     duration: int,
     *     equipment?: array<int, array{id: int, quantity: int}>,
     *     payment_method: string,
     * }  $data
     * @return array{booking: Booking, payment: Payment|null, checkout_url: string|null}
     */
    public function create(array $data): array
    {
        $method = PaymentMethod::from($data['payment_method']);

        $user = $this->resolveCustomer($data['customer']);

        // Cash is settled at the counter, so the booking is confirmed up front;
        // GCash needs a pending hold while the customer completes checkout.
        $status = $method === PaymentMethod::Cash
            ? BookingStatus::Confirmed
            : BookingStatus::PendingPayment;

        $booking = $this->bookings->create([
            'user_id' => $user->id,
            'court_id' => $data['court_id'],
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $this->endTime($data['start_time'], $data['duration']),
            'equipment' => $data['equipment'] ?? [],
        ], $status);

        if ($method === PaymentMethod::Cash) {
            $payment = $this->payments->recordCashPayment($booking);

            return ['booking' => $booking->refresh(), 'payment' => $payment, 'checkout_url' => null];
        }

        $payment = $this->payments->initiate($booking, PaymentMethod::GCash);

        return ['booking' => $booking, 'payment' => $payment, 'checkout_url' => $payment->checkout_url];
    }

    /**
     * Resolve the booking's customer: an existing account by id, or a new guest
     * record captured at the counter.
     *
     * @param  array{mode: string, user_id?: int|null, name?: string|null, phone?: string|null, email?: string|null}  $customer
     */
    private function resolveCustomer(array $customer): User
    {
        if (($customer['mode'] ?? null) === 'existing' && ! empty($customer['user_id'])) {
            return User::query()->findOrFail($customer['user_id']);
        }

        return DB::transaction(fn (): User => User::create([
            'name' => $customer['name'],
            'email' => $customer['email'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'role' => UserRole::Customer,
            'is_guest' => true,
            'password' => null,
        ]));
    }

    /**
     * Derive the whole-hour end time from a start time and a duration in hours.
     */
    private function endTime(string $startTime, int $durationHours): string
    {
        $startHour = (int) substr($startTime, 0, 2);

        return sprintf('%02d:00', $startHour + $durationHours);
    }
}
