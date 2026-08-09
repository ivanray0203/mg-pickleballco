<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenPlayRegistration>
 */
class OpenPlayRegistrationFactory extends Factory
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
            'user_id' => User::factory(),
            'payment_id' => null,
            'status' => RegistrationStatus::Confirmed,
            'checked_in_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => RegistrationStatus::Reserved]);
    }

    public function waitlisted(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => RegistrationStatus::Waitlisted]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RegistrationStatus::CheckedIn,
            'checked_in_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
