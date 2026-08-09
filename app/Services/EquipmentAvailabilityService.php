<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BookingEquipment;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Answers "how many units of this equipment are free for a given time range",
 * accounting for units already reserved by other bookings that overlap it.
 *
 * Reserved = sum of line quantities on bookings that still reserve their slot
 * (confirmed/completed, or a pending hold that hasn't expired) and whose time
 * range overlaps the requested one.
 */
class EquipmentAvailabilityService
{
    /**
     * Units of the given equipment reserved by overlapping bookings on a date.
     *
     * When {@see $lock} is true the underlying rows are locked FOR UPDATE so a
     * concurrent booking transaction cannot reserve the same units — this must
     * only be used inside a database transaction.
     */
    public function reservedQuantity(
        int $equipmentId,
        Carbon $date,
        string $start,
        string $end,
        ?int $ignoreBookingId = null,
        bool $lock = false,
    ): int {
        $query = BookingEquipment::query()
            ->join('bookings', 'bookings.id', '=', 'booking_equipment.booking_id')
            ->where('booking_equipment.equipment_id', $equipmentId)
            ->whereDate('bookings.booking_date', $date->toDateString())
            ->where('bookings.start_time', '<', $end)
            ->where('bookings.end_time', '>', $start)
            ->whereIn('bookings.status', BookingStatus::blockingValues())
            ->where(function (Builder $inner): void {
                // Mirror Booking::scopeReservesSlot: a pending hold whose
                // expiry has passed no longer reserves its units.
                $inner->where('bookings.status', '!=', BookingStatus::PendingPayment->value)
                    ->orWhereNull('bookings.expires_at')
                    ->orWhere('bookings.expires_at', '>', Carbon::now());
            })
            ->when($ignoreBookingId, fn ($q) => $q->where('bookings.id', '!=', $ignoreBookingId));

        if ($lock) {
            $query->lockForUpdate();
        }

        return (int) $query->sum('booking_equipment.quantity');
    }

    /**
     * Units of the given equipment still available for the requested range.
     */
    public function availableQuantity(
        Equipment $equipment,
        Carbon $date,
        string $start,
        string $end,
        ?int $ignoreBookingId = null,
        bool $lock = false,
    ): int {
        $reserved = $this->reservedQuantity($equipment->id, $date, $start, $end, $ignoreBookingId, $lock);

        return max(0, $equipment->total_quantity - $reserved);
    }

    /**
     * Build the availability catalogue for the client picker: every active
     * equipment item with the number of units free for the given range.
     *
     * @return array<int, array{
     *     id: int, name: string, type: string, type_label: string,
     *     hourly_rate: float, hourly_rate_formatted: string,
     *     available: int, image_url: string|null
     * }>
     */
    public function catalogueFor(Carbon $date, string $start, string $end): array
    {
        return Equipment::query()
            ->active()
            ->where('total_quantity', '>', 0)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(function (Equipment $equipment) use ($date, $start, $end): array {
                $rate = $equipment->hourly_rate / 100;

                return [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'type' => $equipment->type->value,
                    'type_label' => $equipment->type->label(),
                    'hourly_rate' => round($rate, 2),
                    'hourly_rate_formatted' => '₱'.number_format($rate, 2),
                    'available' => $this->availableQuantity($equipment, $date, $start, $end),
                    'image_url' => $equipment->image_url,
                ];
            })
            ->all();
    }
}
