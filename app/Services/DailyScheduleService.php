<?php

namespace App\Services;

use App\Enums\OpenPlaySessionStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\OpenPlaySession;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailyScheduleService
{
    public function __construct(private CourtAvailabilityService $availability) {}

    /**
     * Build a Monday-to-Sunday board for every court: one hour-by-weekday grid
     * per court, plus a week-level summary. Powers the admin court booking page.
     *
     * @return array{
     *     start_date: string,
     *     end_date: string,
     *     range_label: string,
     *     prev_date: string,
     *     next_date: string,
     *     today: string,
     *     days: array<int, array{date: string, weekday_label: string, day_label: string, is_today: bool}>,
     *     hours: array<int, array{hour: string, label: string}>,
     *     courts: array<int, array{
     *         id: int, name: string, type_label: string, is_active: bool,
     *         summary: array{available: int, booked: int, session: int},
     *         cells: array<string, array<string, array{status: string, detail: ?string, title: ?string}>>
     *     }>
     * }
     */
    public function weekBoard(Carbon $weekStart): array
    {
        $start = $weekStart->copy()->startOfWeek(CarbonInterface::MONDAY);
        $end = $start->copy()->addDays(6);

        $dates = collect(range(0, 6))->map(fn (int $offset): Carbon => $start->copy()->addDays($offset));
        $today = Carbon::today();

        $courts = Court::query()->orderByDesc('is_active')->orderBy('name')->get();

        $boards = $courts->map(fn (Court $court): array => $this->courtWeek($court, $dates, $start, $end))->all();

        // Every hour any court is open at some point during the week, so all
        // grids share one row set and stay visually aligned side by side.
        $hours = collect($boards)
            ->flatMap(fn (array $board): array => $board['hours'])
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $hour): array => [
                'hour' => $hour,
                'label' => Carbon::createFromFormat('H:i', $hour)->format('g:i A'),
            ])
            ->all();

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'range_label' => $this->rangeLabel($start, $end),
            'prev_date' => $start->copy()->subWeek()->toDateString(),
            'next_date' => $start->copy()->addWeek()->toDateString(),
            'today' => $today->toDateString(),
            'days' => $dates->map(fn (Carbon $date): array => [
                'date' => $date->toDateString(),
                'weekday_label' => $date->format('D'),
                'day_label' => $date->format('M j'),
                'is_today' => $date->isSameDay($today),
            ])->all(),
            'hours' => $hours,
            'courts' => array_map(fn (array $board): array => Arr::except($board, 'hours'), $boards),
        ];
    }

    /**
     * Build one court's hour-by-weekday grid plus its week summary.
     *
     * @param  Collection<int, Carbon>  $dates
     * @return array{
     *     id: int, name: string, type_label: string, is_active: bool,
     *     summary: array{available: int, booked: int, session: int},
     *     cells: array<string, array<string, array{status: string, detail: ?string, title: ?string}>>,
     *     hours: array<int, string>
     * }
     */
    private function courtWeek(Court $court, Collection $dates, Carbon $start, Carbon $end): array
    {
        $cells = [];
        $hours = [];
        $available = 0;

        foreach ($dates as $date) {
            $slots = $this->slotsForDate($court, $date);

            foreach ($slots as $hour => $cell) {
                $hours[] = $hour;

                if ($cell['status'] === 'available') {
                    $available++;
                }
            }

            $cells[$date->toDateString()] = $slots;
        }

        return [
            'id' => $court->id,
            'name' => $court->name,
            'type_label' => $court->type->label(),
            'is_active' => (bool) $court->is_active,
            'summary' => [
                'available' => $available,
                'booked' => $court->bookings()
                    ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
                    ->reservesSlot()
                    ->count(),
                'session' => OpenPlaySession::query()
                    ->where('court_id', $court->id)
                    ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
                    ->whereIn('status', OpenPlaySessionStatus::blockingValues())
                    ->count(),
            ],
            'cells' => $cells,
            'hours' => array_values(array_unique($hours)),
        ];
    }

    /**
     * Resolve one court-day into an "HH:00" keyed map of cells. Hours outside
     * the court's operating schedule are simply absent — the grid renders them
     * as closed.
     *
     * @return array<string, array{status: string, detail: ?string, title: ?string}>
     */
    private function slotsForDate(Court $court, Carbon $date): array
    {
        $breakdown = $this->availability->forDate($court, $date);

        $bookingsByHour = $this->bookingDetailByHour($court, $date);
        $sessionsByHour = $this->sessionDetailByHour($court, $date);

        $cells = [];

        foreach ($breakdown['slots'] as $slot) {
            $hour = $slot['start_time'];

            if (isset($bookingsByHour[$hour])) {
                $cells[$hour] = [
                    'status' => 'booked',
                    'detail' => null,
                    'title' => $bookingsByHour[$hour],
                ];
            } elseif (isset($sessionsByHour[$hour])) {
                $cells[$hour] = [
                    'status' => 'session',
                    'detail' => $sessionsByHour[$hour]['occupancy'],
                    'title' => $sessionsByHour[$hour]['title'],
                ];
            } elseif ($slot['status'] === CourtAvailabilityService::STATUS_BLOCKED) {
                $cells[$hour] = [
                    'status' => 'blocked',
                    'detail' => null,
                    'title' => $slot['reason'],
                ];
            } else {
                $cells[$hour] = ['status' => 'available', 'detail' => null, 'title' => null];
            }
        }

        return $cells;
    }

    /**
     * "May 7 – May 13, 2026", or a year on both sides when the week straddles
     * a new year.
     */
    private function rangeLabel(Carbon $start, Carbon $end): string
    {
        if ($start->year !== $end->year) {
            return $start->format('M j, Y').' – '.$end->format('M j, Y');
        }

        return $start->format('M j').' – '.$end->format('M j, Y');
    }

    /**
     * Map each occupied hour ("HH:00") to a short booking descriptor.
     *
     * @return array<string, string>
     */
    private function bookingDetailByHour(Court $court, Carbon $date): array
    {
        $map = [];

        $bookings = $court->bookings()
            ->whereDate('booking_date', $date->toDateString())
            ->reservesSlot()
            ->with('user')
            ->get();

        foreach ($bookings as $booking) {
            $detail = trim(($booking->user?->name ?? 'Booking').' · '.$booking->booking_reference);

            foreach ($this->hoursBetween($booking->start_time, $booking->end_time) as $hour) {
                $map[$hour] = $detail;
            }
        }

        return $map;
    }

    /**
     * Map each occupied hour ("HH:00") to its open-play session's title and
     * "taken/capacity" occupancy.
     *
     * @return array<string, array{title: string, occupancy: string}>
     */
    private function sessionDetailByHour(Court $court, Carbon $date): array
    {
        $map = [];

        $sessions = OpenPlaySession::query()
            ->where('court_id', $court->id)
            ->whereDate('session_date', $date->toDateString())
            ->whereIn('status', OpenPlaySessionStatus::blockingValues())
            ->get();

        foreach ($sessions as $session) {
            $detail = [
                'title' => $session->title,
                'occupancy' => $session->occupiedSlots().'/'.$session->capacity,
            ];

            foreach ($this->hoursBetween($session->start_time, $session->end_time) as $hour) {
                $map[$hour] = $detail;
            }
        }

        return $map;
    }

    /**
     * Whole-hour keys ("HH:00") covered by a [start, end) time range.
     *
     * @return array<int, string>
     */
    private function hoursBetween(string $start, string $end): array
    {
        $startHour = (int) substr($start, 0, 2);
        $endHour = (int) substr($end, 0, 2);

        $hours = [];

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $hours[] = sprintf('%02d:00', $hour);
        }

        return $hours;
    }

    /**
     * Build a court-by-hour availability matrix for a single date, reusing
     * the shared {@see CourtAvailabilityService}. Cells outside a court's
     * operating hours are reported as "closed".
     *
     * @return array{
     *     date: string,
     *     day_label: string,
     *     courts: array<int, array{id: int, name: string}>,
     *     rows: array<int, array{hour: string, label: string, cells: array<int, array{court_id: int, status: string, reason: ?string}>}>
     * }
     */
    public function forDate(Carbon $date): array
    {
        $courts = Court::query()->active()->orderBy('name')->get();

        $availabilityByCourt = $courts->mapWithKeys(fn (Court $court): array => [
            $court->id => collect($this->availability->forDate($court, $date)['slots'])->keyBy('start_time'),
        ]);

        // The union of every open hour across all courts, sorted ascending.
        $hours = $availabilityByCourt
            ->flatMap(fn ($slots) => $slots->keys())
            ->unique()
            ->sort()
            ->values();

        $rows = $hours->map(function (string $hour) use ($courts, $availabilityByCourt): array {
            $cells = $courts->map(function (Court $court) use ($hour, $availabilityByCourt): array {
                $slot = $availabilityByCourt->get($court->id)?->get($hour);

                return [
                    'court_id' => $court->id,
                    'status' => $slot['status'] ?? 'closed',
                    'reason' => $slot['reason'] ?? null,
                ];
            })->values()->all();

            return [
                'hour' => $hour,
                'label' => Carbon::createFromFormat('H:i', $hour)->format('g:i A'),
                'cells' => $cells,
            ];
        })->values()->all();

        return [
            'date' => $date->toDateString(),
            'day_label' => $date->format('l'),
            'courts' => $courts->map(fn (Court $court): array => [
                'id' => $court->id,
                'name' => $court->name,
            ])->values()->all(),
            'rows' => $rows,
        ];
    }
}
