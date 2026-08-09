<?php

namespace Database\Factories;

use App\Enums\CourtType;
use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->streetName().' Court';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(CourtType::cases()),
            'hourly_rate' => fake()->numberBetween(1500, 6000),
            'opening_time' => '08:00',
            'closing_time' => '22:00',
            'image_path' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the court is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate the court is indoor.
     */
    public function indoor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CourtType::Indoor,
        ]);
    }

    /**
     * Indicate the court is outdoor.
     */
    public function outdoor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CourtType::Outdoor,
        ]);
    }
}
