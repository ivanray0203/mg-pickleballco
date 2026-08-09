<?php

namespace Database\Factories;

use App\Enums\CourtBlockReason;
use App\Models\Court;
use App\Models\CourtBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourtBlock>
 */
class CourtBlockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'court_id' => Court::factory(),
            'block_date' => now()->addDay()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'reason' => fake()->randomElement(CourtBlockReason::cases()),
            'notes' => null,
            'created_by' => null,
        ];
    }
}
