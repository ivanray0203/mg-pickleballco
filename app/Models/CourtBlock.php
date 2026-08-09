<?php

namespace App\Models;

use App\Enums\CourtBlockReason;
use Database\Factories\CourtBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $court_id
 * @property Carbon $block_date
 * @property string $start_time
 * @property string $end_time
 * @property CourtBlockReason $reason
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['court_id', 'block_date', 'start_time', 'end_time', 'reason', 'notes', 'created_by'])]
class CourtBlock extends Model
{
    /** @use HasFactory<CourtBlockFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'block_date' => 'date',
            'reason' => CourtBlockReason::class,
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
