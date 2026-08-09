<?php

namespace Database\Factories;

use App\Enums\EquipmentType;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * The model the factory builds (the class name is not the table name).
     *
     * @var class-string<Equipment>
     */
    protected $model = Equipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->randomElement(['Paddle', 'Ball Set', 'Shoes']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(EquipmentType::cases()),
            'hourly_rate' => fake()->numberBetween(1000, 5000),
            'total_quantity' => fake()->numberBetween(1, 20),
            'image_path' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the equipment is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate the equipment is a paddle.
     */
    public function paddle(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => EquipmentType::Paddle,
        ]);
    }

    /**
     * Indicate the equipment is a ball.
     */
    public function ball(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => EquipmentType::Ball,
        ]);
    }
}
