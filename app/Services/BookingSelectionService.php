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
 * Customer-facing output deliberately reduces each slot to "available or not"
 * (never exposing why a slot is blocked) and annotates each available start
 * with how many consecutive whole hours can be booked from it.
 */
class BookingSelectionService
{
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
     *     slots: list<array{time: string, label: string, available: bool, max_duration: int}>
     * }
     */
    public function forCourtAndDate(Court $court, Carbon $date): array
    {
        $date = $date->copy()->startOfDay();
        $today = Carbon::today();
        $maxAdvance = $today->copy()->addDays($this->settings->advanceBookingDays());
        $maxHours = $this->settings->maxBookingHours();

        // Past dates and dates beyond the advance-booking limit are not bookable.
        $bookableDate = $date->gte($today) && $date->lte($maxAdvance);

        $breakdown = $this->availability->forDate($court, $date);

        $slots = ($bookableDate && $breakdown['is_open'])
            ? $this->annotateDurations($breakdown['slots'], $maxHours)
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
     * Reduce shared availability slots to client-safe entries, annotating each
     * available start with the number of consecutive available whole hours
     * (capped by the maximum booking duration). Slots are contiguous hours, so
     * consecutive index equals consecutive hour.
     *
     * @param  array<int, array{start_time: string, end_time: string, label: string, status: string, reason: ?string}>  $slots
     * @return list<array{time: string, label: string, available: bool, max_duration: int}>
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
                'available' => $available[$index],
                'max_duration' => $maxDuration,
            ];
        }

        return $result;
    }
}
