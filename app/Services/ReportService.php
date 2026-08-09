<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Carbon;

/**
 * MVP reporting over a date range. The range is applied to the booking's play
 * date (bookings.booking_date) so revenue and booking breakdowns share one
 * consistent axis. Revenue counts only successfully paid payments — failed,
 * expired and pending are always excluded.
 *
 * Every metric is a single SQL aggregate; no rows are pulled into PHP to be
 * summed or counted.
 */
class ReportService
{
    /**
     * Resolve a preset (or custom) range into an inclusive [from, to] of dates,
     * using the application's configured timezone via Carbon.
     *
     * @return array{from: Carbon, to: Carbon, preset: string}
     */
    public function resolveRange(string $preset, ?string $from = null, ?string $to = null): array
    {
        return match ($preset) {
            'today' => ['from' => Carbon::today(), 'to' => Carbon::today(), 'preset' => 'today'],
            'week' => ['from' => Carbon::now()->startOfWeek(), 'to' => Carbon::now()->endOfWeek(), 'preset' => 'week'],
            'custom' => $this->customRange($from, $to),
            default => ['from' => Carbon::now()->startOfMonth(), 'to' => Carbon::now()->endOfMonth(), 'preset' => 'month'],
        };
    }

    /**
     * @return array{
     *     summary: array<string, mixed>,
     *     revenue_by_court: list<array{court: string, amount: float, amount_formatted: string}>,
     *     bookings_by_court: list<array{court: string, count: int}>,
     *     bookings_by_day: list<array{date: string, count: int}>,
     *     peak_hours: list<array{hour: string, label: string, count: int}>
     * }
     */
    public function build(Carbon $from, Carbon $to): array
    {
        return [
            'summary' => $this->summary($from, $to),
            'revenue_by_court' => $this->revenueByCourt($from, $to),
            'bookings_by_court' => $this->bookingsByCourt($from, $to),
            'bookings_by_day' => $this->bookingsByDay($from, $to),
            'peak_hours' => $this->peakHours($from, $to),
        ];
    }

    /**
     * @return array{
     *     total_revenue: float,
     *     total_revenue_formatted: string,
     *     total_bookings: int,
     *     confirmed_bookings: int,
     *     cancelled_bookings: int,
     *     average_booking_value: float,
     *     average_booking_value_formatted: string
     * }
     */
    private function summary(Carbon $from, Carbon $to): array
    {
        $bookingStats = $this->inRange(Booking::query(), 'booking_date', $from, $to)
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as confirmed', [BookingStatus::Confirmed->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as cancelled', [BookingStatus::Cancelled->value])
            ->selectRaw('coalesce(avg(total_amount), 0) as avg_value')
            ->first();

        $totalRevenue = $this->paidAmount($from, $to);
        $avgValue = (int) round((float) ($bookingStats->avg_value ?? 0));

        return [
            'total_revenue' => round($totalRevenue / 100, 2),
            'total_revenue_formatted' => $this->money($totalRevenue),
            'total_bookings' => (int) ($bookingStats->total ?? 0),
            'confirmed_bookings' => (int) ($bookingStats->confirmed ?? 0),
            'cancelled_bookings' => (int) ($bookingStats->cancelled ?? 0),
            'average_booking_value' => round($avgValue / 100, 2),
            'average_booking_value_formatted' => $this->money($avgValue),
        ];
    }

    /**
     * @return list<array{court: string, amount: float, amount_formatted: string}>
     */
    private function revenueByCourt(Carbon $from, Carbon $to): array
    {
        return $this->inRange(
            Payment::query()
                ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
                ->join('courts', 'courts.id', '=', 'bookings.court_id')
                ->where('payments.status', PaymentStatus::Paid->value),
            'bookings.booking_date',
            $from,
            $to,
        )
            ->groupBy('courts.id', 'courts.name')
            ->orderByDesc('amount')
            ->selectRaw('courts.name as court, sum(payments.amount) as amount')
            ->get()
            ->map(fn ($row): array => [
                'court' => $row->court,
                'amount' => round((int) $row->amount / 100, 2),
                'amount_formatted' => $this->money((int) $row->amount),
            ])
            ->all();
    }

    /**
     * @return list<array{court: string, count: int}>
     */
    private function bookingsByCourt(Carbon $from, Carbon $to): array
    {
        return $this->inRange(
            Booking::query()->join('courts', 'courts.id', '=', 'bookings.court_id'),
            'bookings.booking_date',
            $from,
            $to,
        )
            ->groupBy('courts.id', 'courts.name')
            ->orderByDesc('count')
            ->selectRaw('courts.name as court, count(*) as count')
            ->get()
            ->map(fn ($row): array => ['court' => $row->court, 'count' => (int) $row->count])
            ->all();
    }

    /**
     * @return list<array{date: string, count: int}>
     */
    private function bookingsByDay(Carbon $from, Carbon $to): array
    {
        return $this->inRange(Booking::query(), 'booking_date', $from, $to)
            ->groupBy('booking_date')
            ->orderBy('booking_date')
            ->selectRaw('booking_date as date, count(*) as count')
            ->get()
            ->map(fn ($row): array => [
                'date' => Carbon::parse($row->date)->toDateString(),
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * @return list<array{hour: string, label: string, count: int}>
     */
    private function peakHours(Carbon $from, Carbon $to): array
    {
        return $this->inRange(Booking::query()->blocking(), 'booking_date', $from, $to)
            ->groupBy('start_time')
            ->orderBy('start_time')
            ->selectRaw('start_time, count(*) as count')
            ->get()
            ->map(function ($row): array {
                $hour = substr((string) $row->start_time, 0, 5);

                return [
                    'hour' => $hour,
                    'label' => Carbon::createFromFormat('H:i', $hour)->format('g A'),
                    'count' => (int) $row->count,
                ];
            })
            ->all();
    }

    private function paidAmount(Carbon $from, Carbon $to): int
    {
        return (int) $this->inRange(
            Payment::query()
                ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
                ->where('payments.status', PaymentStatus::Paid->value),
            'bookings.booking_date',
            $from,
            $to,
        )->sum('payments.amount');
    }

    /**
     * @return array{from: Carbon, to: Carbon, preset: string}
     */
    private function customRange(?string $from, ?string $to): array
    {
        try {
            $start = $from !== null ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
            $end = $to !== null ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfMonth();
        } catch (\Throwable) {
            return ['from' => Carbon::now()->startOfMonth(), 'to' => Carbon::now()->endOfMonth(), 'preset' => 'custom'];
        }

        // Guard against an inverted range.
        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return ['from' => $start, 'to' => $end, 'preset' => 'custom'];
    }

    /**
     * Constrain a query to an inclusive date range on the given column.
     * Uses whereDate so a datetime-backed date column is normalized to its
     * date part before comparison.
     *
     * @template TBuilder of \Illuminate\Database\Eloquent\Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    private function inRange($query, string $column, Carbon $from, Carbon $to)
    {
        return $query
            ->whereDate($column, '>=', $from->toDateString())
            ->whereDate($column, '<=', $to->toDateString());
    }

    private function money(int $centavos): string
    {
        return '₱'.number_format($centavos / 100, 2);
    }
}
