<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Court;
use App\Models\CourtSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourtSchedule>
 */
class CourtScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'court_id' => Court::factory(),
            'day_of_week' => fake()->randomElement(DayOfWeek::cases()),
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_open' => true,
        ];
    }

    /**
     * Indicate the day is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_open' => false,
            'opening_time' => null,
            'closing_time' => null,
        ]);
    }
}
