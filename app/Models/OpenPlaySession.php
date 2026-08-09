<?php

namespace App\Models;

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use Database\Factories\OpenPlaySessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $court_id
 * @property string $title
 * @property Carbon $session_date
 * @property string $start_time
 * @property string $end_time
 * @property int $capacity
 * @property int $court_count
 * @property SkillLevel|null $skill_level
 * @property int $price_amount Flat per-player fee, stored as integer cents.
 * @property OpenPlaySessionStatus $status
 * @property string|null $notes
 * @property Carbon|null $reminder_sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['court_id', 'title', 'session_date', 'start_time', 'end_time', 'capacity', 'court_count', 'skill_level', 'price_amount', 'status', 'notes', 'reminder_sent_at'])]
class OpenPlaySession extends Model
{
    /** @use HasFactory<OpenPlaySessionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'capacity' => 'integer',
            'court_count' => 'integer',
            'skill_level' => SkillLevel::class,
            'price_amount' => 'integer',
            'status' => OpenPlaySessionStatus::class,
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return HasMany<OpenPlayRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(OpenPlayRegistration::class);
    }

    /**
     * @return HasMany<OpenPlayGame, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(OpenPlayGame::class);
    }

    /**
     * Whether the session is free to join.
     */
    public function isFree(): bool
    {
        return $this->price_amount === 0;
    }

    /**
     * Count registrations that currently occupy a capacity slot.
     */
    public function occupiedSlots(): int
    {
        return $this->registrations()
            ->whereIn('status', RegistrationStatus::occupyingValues())
            ->count();
    }

    /**
     * How many capacity slots remain open.
     */
    public function remainingSlots(): int
    {
        return max(0, $this->capacity - $this->occupiedSlots());
    }

    /**
     * Whether the session has reached capacity.
     */
    public function isFull(): bool
    {
        return $this->remainingSlots() === 0;
    }

    /**
     * Scope to sessions dated today or later.
     *
     * @param  Builder<OpenPlaySession>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereDate('session_date', '>=', Carbon::today());
    }
}
