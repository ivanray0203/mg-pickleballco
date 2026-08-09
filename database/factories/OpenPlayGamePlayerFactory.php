<?php

namespace Database\Factories;

use App\Models\OpenPlayGame;
use App\Models\OpenPlayGamePlayer;
use App\Models\OpenPlayRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenPlayGamePlayer>
 */
class OpenPlayGamePlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'open_play_game_id' => OpenPlayGame::factory(),
            'open_play_registration_id' => OpenPlayRegistration::factory(),
            'team' => fake()->randomElement(['a', 'b']),
        ];
    }

    public function onTeam(string $team): static
    {
        return $this->state(fn (array $attributes): array => ['team' => $team]);
    }
}
