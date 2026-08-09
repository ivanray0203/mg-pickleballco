<?php

namespace App\Models;

use App\Enums\MatchupStatus;
use Database\Factories\OpenPlayMatchupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An auto-generated 2v2 matchup in a session's play queue. Scoring a matchup
 * creates an authoritative {@see OpenPlayGame} via the normal recording path.
 *
 * @property int $id
 * @property int $open_play_session_id
 * @property array<int, int> $team_a
 * @property array<int, int> $team_b
 * @property int $position
 * @property MatchupStatus $status
 * @property string|null $court_label
 * @property int|null $open_play_game_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['open_play_session_id', 'team_a', 'team_b', 'position', 'status', 'court_label', 'open_play_game_id'])]
class OpenPlayMatchup extends Model
{
    /** @use HasFactory<OpenPlayMatchupFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'team_a' => 'array',
            'team_b' => 'array',
            'position' => 'integer',
            'status' => MatchupStatus::class,
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
     * @return BelongsTo<OpenPlayGame, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(OpenPlayGame::class, 'open_play_game_id');
    }
}
