<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read-only reporting over customers for the admin panel: the paginated list
 * with aggregates, per-customer statistics, and history collections. All
 * aggregates are computed in SQL to avoid N+1 queries.
 */
class CustomerService
{
    /**
     * Paginate customers with booking/payment aggregates and an optional
     * name/email search. Counts and the paid total are resolved as subqueries
     * so listing N customers stays a constant number of queries.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateList(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->customers()
            ->when($search, fn ($query, $term) => $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            }))
            ->withCount([
                'bookings',
                'bookings as upcoming_bookings_count' => fn ($q) => $q->upcoming(),
            ])
            ->withSum(
                ['payments as total_paid_amount' => fn ($q) => $q->where('payments.status', PaymentStatus::Paid->value)],
                'amount',
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Compute booking and payment statistics for a single customer.
     *
     * @return array{
     *     total_bookings: int,
     *     upcoming_bookings: int,
     *     completed_bookings: int,
     *     cancelled_bookings: int,
     *     total_paid_amount: int,
     *     total_paid_formatted: string
     * }
     */
    public function stats(User $customer): array
    {
        $counts = $customer->bookings()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as completed', [BookingStatus::Completed->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as cancelled', [BookingStatus::Cancelled->value])
            ->first();

        $totalPaid = (int) $customer->payments()
            ->where('payments.status', PaymentStatus::Paid->value)
            ->sum('amount');

        return [
            'total_bookings' => (int) ($counts->total ?? 0),
            'upcoming_bookings' => $customer->bookings()->upcoming()->count(),
            'completed_bookings' => (int) ($counts->completed ?? 0),
            'cancelled_bookings' => (int) ($counts->cancelled ?? 0),
            'total_paid_amount' => $totalPaid,
            'total_paid_formatted' => '₱'.number_format($totalPaid / 100, 2),
        ];
    }

    /**
     * The customer's upcoming reservations, soonest first.
     *
     * @return Collection<int, Booking>
     */
    public function upcomingBookings(User $customer): Collection
    {
        return $customer->bookings()
            ->upcoming()
            ->with(['court', 'user'])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Recent booking history, most recent first.
     *
     * @return Collection<int, Booking>
     */
    public function bookingHistory(User $customer, int $limit = 50): Collection
    {
        return $customer->bookings()
            ->with(['court', 'user'])
            ->latest('booking_date')
            ->latest('start_time')
            ->limit($limit)
            ->get();
    }

    /**
     * Recent payment history, most recent first.
     *
     * @return Collection<int, Payment>
     */
    public function paymentHistory(User $customer, int $limit = 50): Collection
    {
        return $customer->payments()
            ->with(['booking.court', 'booking.user'])
            ->orderByDesc('payments.created_at')
            ->limit($limit)
            ->get();
    }
}
