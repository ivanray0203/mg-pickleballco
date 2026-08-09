<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single equipment rental line on a booking. Rate and name are snapshotted
 * so historical bookings are unaffected by later catalogue changes.
 *
 * @property int $id
 * @property int $booking_id
 * @property int $equipment_id
 * @property string $name
 * @property int $quantity
 * @property int $unit_hourly_rate Stored as an integer number of cents.
 * @property int $line_total Stored as an integer number of cents.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['booking_id', 'equipment_id', 'name', 'quantity', 'unit_hourly_rate', 'line_total'])]
class BookingEquipment extends Model
{
    /**
     * The table name (avoids Eloquent's "booking_equipments" pluralisation).
     */
    protected $table = 'booking_equipment';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_hourly_rate' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Equipment, $this>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
