<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->numberBetween(8, 18);
        $duration = fake()->numberBetween(1, 2);
        $rate = 3000;

        return [
            'booking_reference' => $this->reference(),
            'user_id' => User::factory(),
            'court_id' => Court::factory(),
            'booking_date' => now()->addDays(fake()->numberBetween(1, 14))->toDateString(),
            'start_time' => sprintf('%02d:00', $start),
            'end_time' => sprintf('%02d:00', $start + $duration),
            'duration_hours' => $duration,
            'hourly_rate' => $rate,
            'total_amount' => $rate * $duration,
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
        ];
    }

    /**
     * Build a unique, human-friendly booking reference.
     */
    private function reference(): string
    {
        return 'PB-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)).fake()->unique()->numberBetween(1000, 9999);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::PendingPayment,
            'confirmed_at' => null,
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Cancelled,
            'confirmed_at' => null,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelled by admin.',
        ]);
    }

    /**
     * Place the booking on a specific court, date and hour range.
     */
    public function forSlot(Court $court, string $date, int $startHour, int $endHour): static
    {
        return $this->state(fn (array $attributes): array => [
            'court_id' => $court->id,
            'booking_date' => $date,
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $endHour),
            'duration_hours' => $endHour - $startHour,
            'hourly_rate' => $court->hourly_rate,
            'total_amount' => $court->hourly_rate * ($endHour - $startHour),
        ]);
    }
}
