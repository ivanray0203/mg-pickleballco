<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Database\Factories\CourtScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $court_id
 * @property DayOfWeek $day_of_week
 * @property string|null $opening_time
 * @property string|null $closing_time
 * @property bool $is_open
 */
#[Fillable(['court_id', 'day_of_week', 'opening_time', 'closing_time', 'is_open'])]
class CourtSchedule extends Model
{
    /** @use HasFactory<CourtScheduleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'is_open' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
