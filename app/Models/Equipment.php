<?php

namespace App\Models;

use App\Enums\EquipmentType;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A rentable piece of equipment (paddle, ball, etc.) that a customer can add
 * to a court booking. Priced per hour and constrained by owned stock.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property EquipmentType $type
 * @property int $hourly_rate Stored as an integer number of cents.
 * @property int $total_quantity
 * @property string|null $image_path
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $image_url
 */
#[Fillable(['name', 'slug', 'description', 'type', 'hourly_rate', 'total_quantity', 'image_path', 'is_active'])]
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    /**
     * The disk used to store equipment images.
     */
    public const IMAGE_DISK = 'public';

    /**
     * The table name (Eloquent would otherwise pluralise to "equipment"'s
     * irregular form; the noun is uncountable, so pin it explicitly).
     */
    protected $table = 'equipment';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EquipmentType::class,
            'hourly_rate' => 'integer',
            'total_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The public URL for the equipment's image, if one exists.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? Storage::disk(self::IMAGE_DISK)->url($this->image_path)
            : null;
    }

    /**
     * @return HasMany<BookingEquipment, $this>
     */
    public function bookingLines(): HasMany
    {
        return $this->hasMany(BookingEquipment::class);
    }

    /**
     * Scope a query to only active equipment.
     *
     * @param  Builder<Equipment>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
