<?php

namespace App\Services;

use App\Enums\OpenPlaySessionStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\CourtSchedule;
use App\Models\OpenPlaySession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calculates bookable hourly slots for a court on a given date.
 *
 * This is the single source of truth for availability. It intentionally has
 * no knowledge of the admin panel so the client booking flow can reuse it
 * unchanged. Slots are always whole hours, enforcing the core booking rule.
 *
 * Availability is derived from, in order of precedence:
 *   1. the court's active status,
 *   2. the weekly operating schedule for the date's weekday,
 *   3. court blocks overlapping the slot,
 *   4. existing bookings (integration point — see {@see bookedRanges()}).
 */
class CourtAvailabilityService
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_BOOKED = 'booked';

    /**
     * Build the availability breakdown for a court on a single date.
     *
     * @return array{
     *     date: string,
     *     day_of_week: int,
     *     day_label: string,
     *     court_active: bool,
     *     is_open: bool,
     *     opening_time: ?string,
     *     closing_time: ?string,
     *     slots: array<int, array{start_time: string, end_time: string, label: string, status: string, reason: ?string}>
     * }
     */
    public function forDate(Court $court, Carbon $date): array
    {
        $day = $this->resolveDaySchedule($court, $date);
        $isOpen = $court->is_active && $day['is_open'];

        $slots = $isOpen
            ? $this->buildSlots($court, $date, $day['opening_time'], $day['closing_time'])
            : [];

        return [
            'date' => $date->toDateString(),
            'day_of_week' => (int) $date->dayOfWeek,
            'day_label' => $date->format('l'),
            'court_active' => $court->is_active,
            'is_open' => $isOpen,
            'opening_time' => $isOpen ? $day['opening_time'] : null,
            'closing_time' => $isOpen ? $day['closing_time'] : null,
            'slots' => $slots,
        ];
    }

    /**
     * Determine whether a whole-hour range is fully bookable: the court is
     * active, open that day, within operating hours, and every hour in the
     * range is currently available (not blocked or already booked).
     *
     * This is the single availability check the booking flow relies on, so
     * admin and customer bookings share identical rules.
     */
    public function isBookable(Court $court, Carbon $date, string $start, string $end): bool
    {
        $startMinutes = $this->toMinutes($start);
        $endMinutes = $this->toMinutes($end);

        if ($endMinutes <= $startMinutes) {
            return false;
        }

        $slots = collect($this->forDate($court, $date)['slots'])->keyBy('start_time');

        for ($minutes = $startMinutes; $minutes < $endMinutes; $minutes += 60) {
            $key = sprintf('%02d:00', intdiv($minutes, 60));
            $slot = $slots->get($key);

            if ($slot === null || $slot['status'] !== self::STATUS_AVAILABLE) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether a whole-hour range sits inside the court's operating
     * hours for the given date (and the court is open that day). Reused by
     * block validation so blocks can't be placed outside opening hours.
     */
    public function isWithinOperatingHours(Court $court, Carbon $date, string $start, string $end): bool
    {
        $day = $this->resolveDaySchedule($court, $date);

        if (! $day['is_open'] || $day['opening_time'] === null || $day['closing_time'] === null) {
            return false;
        }

        return $this->toMinutes($start) >= $this->toMinutes($day['opening_time'])
            && $this->toMinutes($end) <= $this->toMinutes($day['closing_time']);
    }

    /**
     * Resolve the effective schedule for a weekday, falling back to the
     * court's base operating hours when no explicit schedule row exists.
     *
     * @return array{is_open: bool, opening_time: ?string, closing_time: ?string}
     */
    private function resolveDaySchedule(Court $court, Carbon $date): array
    {
        /** @var CourtSchedule|null $schedule */
        $schedule = $court->schedules()
            ->where('day_of_week', $date->dayOfWeek)
            ->first();

        if ($schedule) {
            return [
                'is_open' => $schedule->is_open,
                'opening_time' => $schedule->is_open ? $this->normalizeTime($schedule->opening_time) : null,
                'closing_time' => $schedule->is_open ? $this->normalizeTime($schedule->closing_time) : null,
            ];
        }

        return [
            'is_open' => true,
            'opening_time' => $this->normalizeTime($court->opening_time),
            'closing_time' => $this->normalizeTime($court->closing_time),
        ];
    }

    /**
     * Generate the hourly slots between opening and closing, marking each
     * with its status.
     *
     * @return array<int, array{start_time: string, end_time: string, label: string, status: string, reason: ?string}>
     */
    private function buildSlots(Court $court, Carbon $date, ?string $opening, ?string $closing): array
    {
        if ($opening === null || $closing === null) {
            return [];
        }

        $openHour = (int) substr($opening, 0, 2);
        $closeHour = (int) substr($closing, 0, 2);

        $blocks = $court->blocks()
            ->whereDate('block_date', $date->toDateString())
            ->get();

        $booked = $this->bookedRanges($court, $date);
        $sessions = $this->openPlaySessionRanges($court, $date);

        $slots = [];

        for ($hour = $openHour; $hour < $closeHour; $hour++) {
            $slotStart = $hour * 60;
            $slotEnd = ($hour + 1) * 60;

            [$status, $reason] = $this->resolveSlotStatus($slotStart, $slotEnd, $blocks, $booked, $sessions);

            $slots[] = [
                'start_time' => sprintf('%02d:00', $hour),
                'end_time' => sprintf('%02d:00', $hour + 1),
                'label' => Carbon::createFromTime($hour)->format('g:i A'),
                'status' => $status,
                'reason' => $reason,
            ];
        }

        return $slots;
    }

    /**
     * Decide a slot's status. Booked wins over blocked, which wins over free.
     * Open-play sessions occupy the window like a booking.
     *
     * @param  Collection<int, CourtBlock>  $blocks
     * @param  Collection<int, array{start: int, end: int}>  $booked
     * @param  Collection<int, array{start: int, end: int}>  $sessions
     * @return array{0: string, 1: ?string}
     */
    private function resolveSlotStatus(int $slotStart, int $slotEnd, Collection $blocks, Collection $booked, Collection $sessions): array
    {
        foreach ($booked as $range) {
            if ($this->overlaps($slotStart, $slotEnd, $range['start'], $range['end'])) {
                return [self::STATUS_BOOKED, null];
            }
        }

        foreach ($sessions as $range) {
            if ($this->overlaps($slotStart, $slotEnd, $range['start'], $range['end'])) {
                return [self::STATUS_BLOCKED, 'Open play session'];
            }
        }

        foreach ($blocks as $block) {
            $blockStart = $this->toMinutes($block->start_time);
            $blockEnd = $this->toMinutes($block->end_time);

            if ($this->overlaps($slotStart, $slotEnd, $blockStart, $blockEnd)) {
                return [self::STATUS_BLOCKED, $block->reason->label()];
            }
        }

        return [self::STATUS_AVAILABLE, null];
    }

    /**
     * Booked time ranges (in minutes) for the date, covering every booking
     * status that occupies a slot. This is the sole place bookings feed into
     * availability, keeping a single algorithm across admin and client.
     *
     * @return Collection<int, array{start: int, end: int}>
     */
    private function bookedRanges(Court $court, Carbon $date): Collection
    {
        return $court->bookings()
            ->whereDate('booking_date', $date->toDateString())
            ->reservesSlot()
            ->get()
            ->map(fn (Booking $booking): array => [
                'start' => $this->toMinutes($booking->start_time),
                'end' => $this->toMinutes($booking->end_time),
            ]);
    }

    /**
     * Active open-play session ranges (in minutes) for the date. Sessions hold
     * the whole court, so they block private bookings over their window.
     *
     * @return Collection<int, array{start: int, end: int}>
     */
    private function openPlaySessionRanges(Court $court, Carbon $date): Collection
    {
        return OpenPlaySession::query()
            ->where('court_id', $court->id)
            ->whereDate('session_date', $date->toDateString())
            ->whereIn('status', OpenPlaySessionStatus::blockingValues())
            ->get()
            ->map(fn (OpenPlaySession $session): array => [
                'start' => $this->toMinutes($session->start_time),
                'end' => $this->toMinutes($session->end_time),
            ]);
    }

    private function overlaps(int $startA, int $endA, int $startB, int $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours * 60) + (int) $minutes;
    }

    private function normalizeTime(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }
}
