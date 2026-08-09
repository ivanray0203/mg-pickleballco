<?php

namespace App\Models;

use Database\Factories\OpenPlayGameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $open_play_session_id
 * @property int $team_a_score
 * @property int $team_b_score
 * @property string $winning_team 'a' or 'b'.
 * @property Carbon|null $played_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['open_play_session_id', 'team_a_score', 'team_b_score', 'winning_team', 'played_at'])]
class OpenPlayGame extends Model
{
    /** @use HasFactory<OpenPlayGameFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'team_a_score' => 'integer',
            'team_b_score' => 'integer',
            'played_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OpenPlaySession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(OpenPlaySession::class, 'open_play_session_id');
    }

    /**
     * @return HasMany<OpenPlayGamePlayer, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(OpenPlayGamePlayer::class);
    }
}
