<?php

namespace App\Models;

use App\Enums\CourtType;
use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property CourtType $type
 * @property int $hourly_rate Stored as an integer number of cents.
 * @property string $opening_time
 * @property string $closing_time
 * @property string|null $image_path
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $image_url
 */
#[Fillable(['name', 'slug', 'description', 'type', 'hourly_rate', 'opening_time', 'closing_time', 'image_path', 'is_active'])]
class Court extends Model
{
    /** @use HasFactory<CourtFactory> */
    use HasFactory;

    /**
     * The disk used to store court images.
     */
    public const IMAGE_DISK = 'public';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CourtType::class,
            'hourly_rate' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The public URL for the court's primary image, if one exists.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? Storage::disk(self::IMAGE_DISK)->url($this->image_path)
            : null;
    }

    /**
     * @return HasMany<CourtSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(CourtSchedule::class);
    }

    /**
     * @return HasMany<CourtBlock, $this>
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(CourtBlock::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Scope a query to only active courts.
     *
     * @param  Builder<Court>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
