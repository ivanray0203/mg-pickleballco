<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Equipment;
use App\Notifications\BookingCancelled;
use App\Settings\AppSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private CourtAvailabilityService $availability,
        private EquipmentAvailabilityService $equipmentAvailability,
        private AppSettings $settings,
    ) {}

    /**
     * Create a booking, enforcing whole-hour boundaries, court availability
     * and overlap rules. The overlap check runs inside a transaction with a
     * row lock so concurrent checkout attempts can't double-book a slot.
     *
     * @param  array{
     *     user_id: int, court_id: int, booking_date: string, start_time: string, end_time: string,
     *     equipment?: array<int, array{id: int, quantity: int}>
     * }  $data
     */
    public function create(array $data, BookingStatus $status = BookingStatus::PendingPayment): Booking
    {
        $date = Carbon::parse($data['booking_date'])->startOfDay();
        $start = $data['start_time'];
        $end = $data['end_time'];

        $this->assertWholeHourRange($start, $end);

        $court = Court::findOrFail($data['court_id']);
        $duration = intdiv($this->toMinutes($end) - $this->toMinutes($start), 60);

        $this->assertWithinLimits($date, $start, $duration);

        return DB::transaction(function () use ($court, $date, $start, $end, $duration, $data, $status) {
            // Concurrency-safe overlap check: lock the court's reserving rows
            // for this date, then verify no active booking/hold overlaps. Two
            // simultaneous holds for the same slot cannot both pass.
            $hasConflict = $court->bookings()
                ->whereDate('booking_date', $date->toDateString())
                ->reservesSlot()
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'start_time' => 'This time range overlaps an existing booking.',
                ]);
            }

            if (! $this->availability->isBookable($court, $date, $start, $end)) {
                throw ValidationException::withMessages([
                    'start_time' => 'The selected time is not available for booking.',
                ]);
            }

            // Price & reserve equipment atomically with the slot. The stock
            // check locks overlapping reservation rows so concurrent bookings
            // can't oversell the same units.
            $equipment = $this->resolveEquipmentLines(
                $data['equipment'] ?? [], $date, $start, $end, $duration, lock: true,
            );

            $booking = Booking::create([
                'booking_reference' => $this->generateReference($date),
                'user_id' => $data['user_id'],
                'court_id' => $court->id,
                'booking_date' => $date->toDateString(),
                'start_time' => $start,
                'end_time' => $end,
                'duration_hours' => $duration,
                'hourly_rate' => $court->hourly_rate,
                'total_amount' => ($court->hourly_rate * $duration) + $equipment['total'],
                'status' => $status,
                'confirmed_at' => $status === BookingStatus::Confirmed ? now() : null,
                // Pending holds expire after the configured duration, freeing
                // the slot if payment is never completed.
                'expires_at' => $status === BookingStatus::PendingPayment
                    ? now()->addMinutes($this->settings->bookingHoldMinutes())
                    : null,
            ]);

            foreach ($equipment['lines'] as $line) {
                $booking->equipmentItems()->create($line);
            }

            return $booking;
        });
    }

    /**
     * Cancel an eligible booking, recording the reason. Historical bookings
     * are never hard-deleted. Refund handling can hook in here later.
     */
    public function cancel(Booking $booking, string $reason): Booking
    {
        $booking = DB::transaction(function () use ($booking, $reason) {
            if (! $booking->isCancellable()) {
                throw ValidationException::withMessages([
                    'status' => 'This booking can no longer be cancelled.',
                ]);
            }

            $booking->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $booking;
        });

        // Notify the customer after commit.
        $booking->user->notify(new BookingCancelled($booking));

        return $booking;
    }

    /**
     * Generate a human-friendly, unique booking reference (PB-YYYYMMDD-XXXX).
     */
    public function generateReference(Carbon $date): string
    {
        do {
            $reference = sprintf('PB-%s-%s', $date->format('Ymd'), Str::upper(Str::random(4)));
        } while (Booking::query()->where('booking_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Enforce the configurable booking limits: maximum duration, how far in
     * advance a booking may be placed, and that the slot is not already in the
     * past. Values come from the settings access layer, never hardcoded here.
     *
     * The past check allows the hour currently in progress so a counter
     * (walk-in) booking can still be taken for the ongoing hour; only hours
     * that have completely elapsed are rejected. The customer-facing selection
     * screen is stricter and offers future hours only.
     */
    private function assertWithinLimits(Carbon $date, string $start, int $duration): void
    {
        if ($duration > $this->settings->maxBookingHours()) {
            throw ValidationException::withMessages([
                'end_time' => "Bookings may not exceed {$this->settings->maxBookingHours()} hours.",
            ]);
        }

        if ($date->lt(Carbon::today())) {
            throw ValidationException::withMessages([
                'booking_date' => 'Bookings cannot be made in the past.',
            ]);
        }

        if ($date->copy()->setTimeFromTimeString($start)->addHour()->lte(Carbon::now())) {
            throw ValidationException::withMessages([
                'start_time' => 'That time has already passed.',
            ]);
        }

        $latest = Carbon::today()->addDays($this->settings->advanceBookingDays());

        if ($date->gt($latest)) {
            throw ValidationException::withMessages([
                'booking_date' => "Bookings can only be made up to {$this->settings->advanceBookingDays()} days in advance.",
            ]);
        }
    }

    /**
     * Derive and validate an authoritative quote for a selection without
     * creating anything. All monetary and time values come from the court and
     * domain rules — never from the client. Throws if the selection is invalid
     * or unavailable.
     *
     * @param  array<int, array{id: int, quantity: int}>  $equipmentSelection
     * @return array{
     *     start_time: string, end_time: string, duration_hours: int,
     *     hourly_rate: int, court_subtotal: int, equipment_total: int, total_amount: int,
     *     equipment_lines: array<int, array{
     *         equipment_id: int, name: string, quantity: int, unit_hourly_rate: int, line_total: int
     *     }>
     * }
     */
    public function previewQuote(Court $court, Carbon $date, string $start, string $end, array $equipmentSelection = []): array
    {
        $this->assertWholeHourRange($start, $end);

        $duration = intdiv($this->toMinutes($end) - $this->toMinutes($start), 60);

        $this->assertWithinLimits($date, $start, $duration);

        if (! $this->availability->isBookable($court, $date, $start, $end)) {
            throw ValidationException::withMessages([
                'start_time' => 'The selected time is not available for booking.',
            ]);
        }

        $equipment = $this->resolveEquipmentLines($equipmentSelection, $date, $start, $end, $duration, lock: false);
        $courtSubtotal = $court->hourly_rate * $duration;

        return [
            'start_time' => $start,
            'end_time' => $end,
            'duration_hours' => $duration,
            'hourly_rate' => $court->hourly_rate,
            'court_subtotal' => $courtSubtotal,
            'equipment_total' => $equipment['total'],
            'total_amount' => $courtSubtotal + $equipment['total'],
            'equipment_lines' => $equipment['lines'],
        ];
    }

    /**
     * Resolve a client equipment selection into authoritative, priced line
     * items, validating that every item is active and has enough free stock
     * for the requested range. All money comes from the catalogue, never the
     * client. Quantities for a repeated equipment id are summed.
     *
     * @param  array<int, array{id: int, quantity: int}>  $selection
     * @return array{
     *     total: int,
     *     lines: array<int, array{
     *         equipment_id: int, name: string, quantity: int, unit_hourly_rate: int, line_total: int
     *     }>
     * }
     */
    private function resolveEquipmentLines(array $selection, Carbon $date, string $start, string $end, int $duration, bool $lock): array
    {
        // Collapse duplicate ids into a single quantity per item.
        $quantities = [];
        foreach ($selection as $item) {
            $id = (int) $item['id'];
            $quantities[$id] = ($quantities[$id] ?? 0) + (int) $item['quantity'];
        }

        if ($quantities === []) {
            return ['total' => 0, 'lines' => []];
        }

        $items = Equipment::query()
            ->active()
            ->whereIn('id', array_keys($quantities))
            ->get();

        $lines = [];
        $total = 0;

        foreach ($quantities as $id => $quantity) {
            $equipment = $items->firstWhere('id', $id);

            if ($equipment === null) {
                throw ValidationException::withMessages([
                    'equipment' => 'One of the selected items is no longer available.',
                ]);
            }

            $available = $this->equipmentAvailability->availableQuantity(
                $equipment, $date, $start, $end, lock: $lock,
            );

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'equipment' => "Only {$available} × {$equipment->name} available for this time.",
                ]);
            }

            $lineTotal = $equipment->hourly_rate * $quantity * $duration;
            $total += $lineTotal;

            $lines[] = [
                'equipment_id' => $equipment->id,
                'name' => $equipment->name,
                'quantity' => $quantity,
                'unit_hourly_rate' => $equipment->hourly_rate,
                'line_total' => $lineTotal,
            ];
        }

        return ['total' => $total, 'lines' => $lines];
    }

    /**
     * Compute the whole-hour end time for a start time and hour duration.
     */
    public function endTime(string $start, int $duration): string
    {
        return sprintf('%02d:00', (int) substr($start, 0, 2) + $duration);
    }

    /**
     * Ensure both boundaries are whole hours and the end is after the start.
     */
    private function assertWholeHourRange(string $start, string $end): void
    {
        foreach (['start_time' => $start, 'end_time' => $end] as $field => $value) {
            if (preg_match('/^([01]\d|2[0-3]):00$/', $value) !== 1) {
                throw ValidationException::withMessages([
                    $field => 'Bookings must start and end on whole hours.',
                ]);
            }
        }

        if ($this->toMinutes($end) <= $this->toMinutes($start)) {
            throw ValidationException::withMessages([
                'end_time' => 'The end time must be after the start time.',
            ]);
        }
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours * 60) + (int) $minutes;
    }
}
