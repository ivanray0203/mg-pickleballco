<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $booking_reference
 * @property int $user_id
 * @property int $court_id
 * @property Carbon $booking_date
 * @property string $start_time
 * @property string $end_time
 * @property int $duration_hours
 * @property int $hourly_rate Stored as an integer number of cents.
 * @property int $total_amount Stored as an integer number of cents.
 * @property BookingStatus $status
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon|null $expires_at
 * @property Carbon|null $reminder_sent_at
 * @property Carbon|null $starting_soon_sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'booking_reference', 'user_id', 'court_id', 'booking_date', 'start_time', 'end_time',
    'duration_hours', 'hourly_rate', 'total_amount', 'status',
    'confirmed_at', 'cancelled_at', 'cancellation_reason', 'expires_at', 'reminder_sent_at',
    'starting_soon_sent_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'duration_hours' => 'integer',
            'hourly_rate' => 'integer',
            'total_amount' => 'integer',
            'status' => BookingStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'starting_soon_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * All payment attempts for the booking. A booking may accumulate several
     * attempts (a customer can retry after a failed or expired checkout), so
     * the full history is preserved rather than a single payment row.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The most recent payment attempt, if any.
     *
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Equipment rental line items attached to this booking.
     *
     * @return HasMany<BookingEquipment, $this>
     */
    public function equipmentItems(): HasMany
    {
        return $this->hasMany(BookingEquipment::class);
    }

    /**
     * Whether the booking may still be cancelled.
     */
    public function isCancellable(): bool
    {
        return $this->status->isCancellable();
    }

    /**
     * Whether this booking no longer reserves its slot because its hold has
     * lapsed (either already marked expired, or a pending hold whose
     * expires_at is in the past but cleanup has not run yet).
     */
    public function isExpired(): bool
    {
        return $this->status === BookingStatus::Expired
            || ($this->status === BookingStatus::PendingPayment
                && $this->expires_at !== null
                && $this->expires_at->isPast());
    }

    /**
     * Scope to bookings whose status blocks a slot from being re-booked.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::blockingValues());
    }

    /**
     * Scope to bookings that currently reserve their slot — the single,
     * central definition of an "active conflict". This is {@see scopeBlocking}
     * minus pending holds whose expires_at has already passed, so a lapsed
     * hold frees the slot even before scheduled cleanup runs.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeReservesSlot(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::blockingValues())
            ->where(function (Builder $inner): void {
                $inner->where('status', '!=', BookingStatus::PendingPayment->value)
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    /**
     * Scope to upcoming reservations: still active (awaiting payment or
     * confirmed) and dated today or later.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
            ->whereDate('booking_date', '>=', Carbon::today());
    }
}
