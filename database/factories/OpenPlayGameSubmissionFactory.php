<?php

namespace Database\Factories;

use App\Enums\OpenPlayGameSubmissionStatus;
use App\Models\OpenPlaySession;
use App\Models\OpenPlayGameSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenPlayGameSubmission>
 */
class OpenPlayGameSubmissionFactory extends Factory
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
            'reported_by_user_id' => User::factory(),
            'team_a' => [],
            'team_b' => [],
            'team_a_score' => 11,
            'team_b_score' => 7,
            'status' => OpenPlayGameSubmissionStatus::Pending,
            'resolved_by_user_id' => null,
            'open_play_game_id' => null,
        ];
    }
}
