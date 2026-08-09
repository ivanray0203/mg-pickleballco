<?php

namespace App\Models;

use App\Enums\OpenPlayGameSubmissionStatus;
use Database\Factories\OpenPlayGameSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A player-reported game result awaiting organizer confirmation. The
 * authoritative {@see OpenPlayGame} is only created when an organizer confirms.
 *
 * @property int $id
 * @property int $open_play_session_id
 * @property int $reported_by_user_id
 * @property array<int, int> $team_a
 * @property array<int, int> $team_b
 * @property int $team_a_score
 * @property int $team_b_score
 * @property OpenPlayGameSubmissionStatus $status
 * @property int|null $resolved_by_user_id
 * @property int|null $open_play_game_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['open_play_session_id', 'reported_by_user_id', 'team_a', 'team_b', 'team_a_score', 'team_b_score', 'status', 'resolved_by_user_id', 'open_play_game_id'])]
class OpenPlayGameSubmission extends Model
{
    /** @use HasFactory<OpenPlayGameSubmissionFactory> */
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
            'team_a_score' => 'integer',
            'team_b_score' => 'integer',
            'status' => OpenPlayGameSubmissionStatus::class,
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
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /**
     * @return BelongsTo<OpenPlayGame, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(OpenPlayGame::class, 'open_play_game_id');
    }
}
