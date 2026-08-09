<?php

namespace Database\Factories;

use App\Enums\OpenPlaySessionStatus;
use App\Enums\SkillLevel;
use App\Models\Court;
use App\Models\OpenPlaySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenPlaySession>
 */
class OpenPlaySessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->numberBetween(8, 18);
        $duration = fake()->numberBetween(2, 3);

        return [
            'court_id' => Court::factory(),
            'title' => fake()->randomElement(['Morning', 'Evening', 'Weekend']).' Open Play',
            'session_date' => now()->addDays(fake()->numberBetween(1, 14))->toDateString(),
            'start_time' => sprintf('%02d:00', $start),
            'end_time' => sprintf('%02d:00', $start + $duration),
            'capacity' => fake()->randomElement([8, 12, 16]),
            'skill_level' => fake()->randomElement([null, ...SkillLevel::cases()]),
            'price_amount' => fake()->randomElement([0, 15000, 20000]),
            'status' => OpenPlaySessionStatus::Open,
            'notes' => null,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes): array => ['price_amount' => 0]);
    }

    public function paid(int $amount = 15000): static
    {
        return $this->state(fn (array $attributes): array => ['price_amount' => $amount]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OpenPlaySessionStatus::Completed,
            'session_date' => now()->subDay()->toDateString(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OpenPlaySessionStatus::Cancelled,
        ]);
    }

    public function forSlot(Court $court, string $date, int $startHour, int $endHour): static
    {
        return $this->state(fn (array $attributes): array => [
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $endHour),
        ]);
    }
}
