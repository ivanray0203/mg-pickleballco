<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'action' => fake()->randomElement(['court.created', 'court.updated', 'booking.cancelled']),
            'auditable_type' => null,
            'auditable_id' => null,
            'metadata' => null,
        ];
    }
}
