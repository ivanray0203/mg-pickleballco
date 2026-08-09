<?php

namespace Database\Factories;

use App\Enums\MatchupStatus;
use App\Models\OpenPlayMatchup;
use App\Models\OpenPlaySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenPlayMatchup>
 */
class OpenPlayMatchupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'open_play_session_id' => OpenPlaySession::factory(),
            'team_a' => [],
            'team_b' => [],
            'position' => 1,
            'status' => MatchupStatus::Queued,
            'open_play_game_id' => null,
        ];
    }
}
