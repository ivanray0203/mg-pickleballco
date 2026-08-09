<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->startOfMonth();

        return [
            'name' => $start->format('F Y').' Season',
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->copy()->endOfMonth()->toDateString(),
            'is_active' => true,
        ];
    }

    /**
     * A season covering the whole current year.
     */
    public function currentYear(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => now()->format('Y').' Season',
            'starts_on' => now()->startOfYear()->toDateString(),
            'ends_on' => now()->endOfYear()->toDateString(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
