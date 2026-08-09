<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Database\Factories\OpenPlayRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $open_play_session_id
 * @property int $user_id
 * @property int|null $payment_id
 * @property RegistrationStatus $status
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['open_play_session_id', 'user_id', 'payment_id', 'status', 'checked_in_at', 'cancelled_at', 'expires_at'])]
class OpenPlayRegistration extends Model
{
    /** @use HasFactory<OpenPlayRegistrationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Whether a paid-registration hold has lapsed before payment completed.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * @return BelongsTo<OpenPlaySession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(OpenPlaySession::class, 'open_play_session_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return HasMany<OpenPlayGamePlayer, $this>
     */
    public function gamePlayers(): HasMany
    {
        return $this->hasMany(OpenPlayGamePlayer::class);
    }
}
