<?php

namespace App\Models;

use Database\Factories\OpenPlayGamePlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $open_play_game_id
 * @property int $open_play_registration_id
 * @property string $team 'a' or 'b'.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['open_play_game_id', 'open_play_registration_id', 'team'])]
class OpenPlayGamePlayer extends Model
{
    /** @use HasFactory<OpenPlayGamePlayerFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<OpenPlayGame, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(OpenPlayGame::class, 'open_play_game_id');
    }

    /**
     * @return BelongsTo<OpenPlayRegistration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(OpenPlayRegistration::class, 'open_play_registration_id');
    }
}
