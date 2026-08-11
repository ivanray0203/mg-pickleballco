<?php

namespace App\Services;

use App\Models\Court;
use App\Settings\AppSettings;
use Illuminate\Support\Carbon;

/**
 * Prepares the customer-facing availability payload for the booking selection
 * screen. It is a thin presenter over the shared {@see CourtAvailabilityService}
 * — the single source of truth for availability — plus the configured booking
 * settings. No availability rules are re-implemented here.
 *
 * Customer-facing output reduces each slot to a coarse state — available,
 * already booked, held by open play, unavailable, or past — and annotates each
 * available start with how many consecutive whole hours can be booked from it.
 * Operational detail (which customer holds a slot, why a court is blocked) is
 * never exposed; "unavailable" covers maintenance and cleaning alike.
 */
class BookingSelectionService
{
    /** A slot taken by another booking. */
    public const STATE_BOOKED = 'booked';

    /** A slot held by an open-play session the customer could join instead. */
    public const STATE_OPEN_PLAY = 'open_play';

    /** A slot on today that has already started. */
    public const STATE_PAST = 'past';

    /** A slot that is free to book. */
    public const STATE_AVAILABLE = 'available';

    /** Anything else holding the slot: a court block or an open-play session. */
    public const STATE_UNAVAILABLE = 'unavailable';

    public function __construct(
        private CourtAvailabilityService $availability,
        private AppSettings $settings,
    ) {}

    /**
     * @return array{
     *     date: string,
     *     day_label: string,
     *     court_active: bool,
     *     is_open: bool,
     *     bookable_date: bool,
     *     max_duration: int,
     *     slots: list<array{time: string, label: string, state: string, available: bool, max_duration: int}>
     * }
     */
    public function forCourtAndDate(Court $court, Carbon $date, bool $includeCurrentHour = false): array
    {
        $date = $date->copy()->startOfDay();
        $today = Carbon::today();
        $maxAdvance = $today->copy()->addDays($this->settings->advanceBookingDays());
        $maxHours = $this->settings->maxBookingHours();

        // Past dates and dates beyond the advance-booking limit are not bookable.
        $bookableDate = $date->gte($today) && $date->lte($maxAdvance);

        $breakdown = $this->availability->forDate($court, $date);

        $slots = ($bookableDate && $breakdown['is_open'])
            ? $this->annotateDurations(
                $this->markElapsedSlots($breakdown['slots'], $date, $includeCurrentHour),
                $maxHours,
            )
            : [];

        return [
            'date' => $breakdown['date'],
            'day_label' => $breakdown['day_label'],
            'court_active' => $breakdown['court_active'],
            'is_open' => $breakdown['is_open'],
            'bookable_date' => $bookableDate,
            'max_duration' => $maxHours,
            'slots' => $slots,
        ];
    }

    /**
     * Restamp today's already-started hours as past so customers can only
     * select hours that are still ahead of them. Slots on other dates are left
     * untouched. Done before duration annotation so an elapsed hour also can't
     * be counted inside a longer block. Past wins over any other state: an
     * hour that is gone can't be acted on whatever else holds it.
     *
     * `$includeCurrentHour` keeps the hour in progress selectable, matching the
     * leniency {@see BookingService} allows so a counter (walk-in) booking can
     * still be taken for the ongoing hour.
     *
     * @param  array<int, array{start_time: string, end_time: string, label: string, status: string, reason: ?string}>  $slots
     * @return array<int, array{start_time: string, end_time: string, label: string, status: string, reason: ?string}>
     */
    private function markElapsedSlots(array $slots, Carbon $date, bool $includeCurrentHour): array
    {
        if (! $date->isToday()) {
            return $slots;
        }

        $now = Carbon::now();
        $cutoff = $includeCurrentHour ? $now->copy()->startOfHour() : $now;

        return array_map(function (array $slot) use ($date, $cutoff): array {
            $start = $date->copy()->setTimeFromTimeString($slot['start_time']);

            if ($start->lt($cutoff)) {
                $slot['status'] = self::STATE_PAST;
                $slot['reason'] = 'Time has passed';
            }

            return $slot;
        }, $slots);
    }

    /**
     * Translate an internal availability status into the coarse state shown to
     * customers. Every kind of block collapses to a single "unavailable" so no
     * operational detail leaks.
     */
    private function toClientState(string $status): string
    {
        return match ($status) {
            CourtAvailabilityService::STATUS_AVAILABLE => self::STATE_AVAILABLE,
            CourtAvailabilityService::STATUS_BOOKED => self::STATE_BOOKED,
            CourtAvailabilityService::STATUS_OPEN_PLAY => self::STATE_OPEN_PLAY,
            self::STATE_PAST => self::STATE_PAST,
            default => self::STATE_UNAVAILABLE,
        };
    }

    /**
     * Reduce shared availability slots to client-safe entries, annotating each
     * available start with the number of consecutive available whole hours
     * (capped by the maximum booking duration). Slots are contiguous hours, so
     * consecutive index equals consecutive hour.
     *
     * @param  array<int, array{start_time: string, end_time: string, label: string, status: string, reason: ?string}>  $slots
     * @return list<array{time: string, label: string, state: string, available: bool, max_duration: int}>
     */
    private function annotateDurations(array $slots, int $maxHours): array
    {
        $available = array_map(
            fn (array $slot): bool => $slot['status'] === CourtAvailabilityService::STATUS_AVAILABLE,
            $slots,
        );
        $count = count($slots);

        $result = [];

        foreach ($slots as $index => $slot) {
            $maxDuration = 0;

            if ($available[$index]) {
                while (
                    $index + $maxDuration < $count
                    && $available[$index + $maxDuration]
                    && $maxDuration < $maxHours
                ) {
                    $maxDuration++;
                }
            }

            $result[] = [
                'time' => $slot['start_time'],
                'label' => $slot['label'],
                'state' => $this->toClientState($slot['status']),
                'available' => $available[$index],
                'max_duration' => $maxDuration,
            ];
        }

        return $result;
    }
}
