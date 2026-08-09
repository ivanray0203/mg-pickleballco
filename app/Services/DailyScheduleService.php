<?php

namespace App\Services;

use App\Enums\OpenPlaySessionStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\OpenPlaySession;
use Illuminate\Support\Carbon;

class DailyScheduleService
{
    public function __construct(private CourtAvailabilityService $availability) {}

    /**
     * Build a per-court day board for every court, each with its own hourly
     * timeline plus booking and open-play detail. Powers the admin daily
     * schedule page, where every court stays visible (cards, not one wide grid).
     *
     * @return array{
     *     date: string,
     *     day_label: string,
     *     courts: array<int, array{
     *         id: int, name: string, is_active: bool, is_open: bool, hours_label: ?string,
     *         summary: array{available: int, booked: int, session: int, blocked: int},
     *         slots: array<int, array{hour: string, label: string, status: string, detail: ?string}>
     *     }>
     * }
     */
    public function boardForDate(Carbon $date): array
    {
        $courts = Court::query()->orderByDesc('is_active')->orderBy('name')->get();

        return [
            'date' => $date->toDateString(),
            'day_label' => $date->format('l'),
            'courts' => $courts->map(fn (Court $court): array => $this->courtBoard($court, $date))->all(),
        ];
    }

    /**
     * Build the day board for a single court.
     *
     * @return array{
     *     id: int, name: string, is_active: bool, is_open: bool, hours_label: ?string,
     *     summary: array{available: int, booked: int, session: int, blocked: int},
     *     slots: array<int, array{hour: string, label: string, status: string, detail: ?string}>
     * }
     */
    private function courtBoard(Court $court, Carbon $date): array
    {
        $breakdown = $this->availability->forDate($court, $date);

        $bookingsByHour = $this->bookingDetailByHour($court, $date);
        $sessionsByHour = $this->sessionDetailByHour($court, $date);

        $summary = ['available' => 0, 'booked' => 0, 'session' => 0, 'blocked' => 0];

        $slots = array_map(function (array $slot) use ($bookingsByHour, $sessionsByHour, &$summary): array {
            $hour = $slot['start_time'];

            if (isset($bookingsByHour[$hour])) {
                $status = 'booked';
                $detail = $bookingsByHour[$hour];
            } elseif (isset($sessionsByHour[$hour])) {
                $status = 'session';
                $detail = $sessionsByHour[$hour];
            } elseif ($slot['status'] === CourtAvailabilityService::STATUS_BLOCKED) {
                $status = 'blocked';
                $detail = $slot['reason'];
            } else {
                $status = 'available';
                $detail = null;
            }

            $summary[$status]++;

            return [
                'hour' => $hour,
                'label' => $slot['label'],
                'status' => $status,
                'detail' => $detail,
            ];
        }, $breakdown['slots']);

        return [
            'id' => $court->id,
            'name' => $court->name,
            'is_active' => (bool) $court->is_active,
            'is_open' => $breakdown['is_open'],
            'hours_label' => $breakdown['is_open']
                ? $this->formatHour($breakdown['opening_time']).' – '.$this->formatHour($breakdown['closing_time'])
                : null,
            'summary' => $summary,
            'slots' => $slots,
        ];
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
     * Map each occupied hour ("HH:00") to a short open-play descriptor.
     *
     * @return array<string, string>
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
            $detail = $session->title.' · '.$session->occupiedSlots().'/'.$session->capacity.' players';

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

    private function formatHour(?string $time): string
    {
        return $time === null ? '' : Carbon::createFromFormat('H:i', substr($time, 0, 5))->format('g:i A');
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
