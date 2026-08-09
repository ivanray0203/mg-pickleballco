<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $payment_reference
 * @property int|null $booking_id
 * @property int|null $open_play_registration_id
 * @property PaymentProvider $provider
 * @property string|null $provider_reference
 * @property PaymentMethod $payment_method
 * @property int $amount Stored as an integer number of centavos.
 * @property PaymentStatus $status
 * @property string|null $checkout_url
 * @property Carbon|null $paid_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $expires_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'payment_reference', 'booking_id', 'open_play_registration_id', 'provider', 'provider_reference', 'payment_method',
    'amount', 'status', 'checkout_url', 'paid_at', 'failed_at', 'expires_at', 'metadata',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
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
     * @return BelongsTo<OpenPlayRegistration, $this>
     */
    public function openPlayRegistration(): BelongsTo
    {
        return $this->belongsTo(OpenPlayRegistration::class);
    }

    /**
     * Whether the payment settled successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    /**
     * Scope to payments in a successful (paid) state.
     *
     * @param  Builder<Payment>  $query
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', PaymentStatus::Paid);
    }
}
