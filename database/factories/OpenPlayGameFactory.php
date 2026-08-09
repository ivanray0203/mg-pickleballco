<?php

namespace Database\Factories;

use App\Models\OpenPlayGame;
use App\Models\OpenPlaySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenPlayGame>
 */
class OpenPlayGameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scoreA = fake()->numberBetween(0, 11);
        $scoreB = $scoreA === 11 ? fake()->numberBetween(0, 9) : 11;

        return [
            'open_play_session_id' => OpenPlaySession::factory(),
            'team_a_score' => $scoreA,
            'team_b_score' => $scoreB,
            'winning_team' => $scoreA > $scoreB ? 'a' : 'b',
            'played_at' => now(),
        ];
    }

    /**
     * Force a specific score line (winner derived from it).
     */
    public function score(int $scoreA, int $scoreB): static
    {
        return $this->state(fn (array $attributes): array => [
            'team_a_score' => $scoreA,
            'team_b_score' => $scoreB,
            'winning_team' => $scoreA > $scoreB ? 'a' : 'b',
        ]);
    }
}
