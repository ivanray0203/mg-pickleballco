<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use Illuminate\Support\Carbon;

/**
 * Builds the admin dashboard payload: today's headline statistics, today's
 * court utilization (via the shared availability engine), and recent activity.
 *
 * All figures are computed with SQL aggregates — no collections of bookings or
 * payments are ever loaded into PHP just to be counted or summed.
 */
class DashboardService
{
    public function __construct(private DailyScheduleService $schedule) {}

    /**
     * @return array{
     *     stats: array<string, mixed>,
     *     schedule: array<string, mixed>,
     *     recentBookings: BookingResource,
     *     recentPayments: PaymentResource
     * }
     */
    public function build(): array
    {
        $today = Carbon::today();

        return [
            'stats' => $this->todayStats($today),
            'schedule' => $this->schedule->forDate($today),
            'recentBookings' => BookingResource::collection(
                Booking::query()->with(['user', 'court'])->latest()->limit(5)->get(),
            ),
            'recentPayments' => PaymentResource::collection(
                Payment::query()->with(['booking.user', 'booking.court'])->latest()->limit(5)->get(),
            ),
        ];
    }

    /**
     * @return array{
     *     today_bookings: int,
     *     today_revenue: float,
     *     today_revenue_formatted: string,
     *     active_courts: int,
     *     pending_payments: int
     * }
     */
    private function todayStats(Carbon $today): array
    {
        $todayBookings = Booking::query()
            ->blocking()
            ->whereDate('booking_date', $today)
            ->count();

        $todayRevenue = (int) Payment::query()
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->where('payments.status', PaymentStatus::Paid->value)
            ->whereDate('bookings.booking_date', $today)
            ->sum('payments.amount');

        return [
            'today_bookings' => $todayBookings,
            'today_revenue' => round($todayRevenue / 100, 2),
            'today_revenue_formatted' => '₱'.number_format($todayRevenue / 100, 2),
            'active_courts' => Court::query()->active()->count(),
            'pending_payments' => Payment::query()
                ->where('status', PaymentStatus::Pending->value)
                ->count(),
        ];
    }
}
