<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_reference' => $this->reference(),
            'booking_id' => Booking::factory(),
            'provider' => PaymentProvider::PayMongo,
            'provider_reference' => 'cs_'.Str::lower(Str::random(20)),
            'payment_method' => PaymentMethod::GCash,
            'amount' => fake()->numberBetween(1500, 12000),
            'status' => PaymentStatus::Pending,
            'checkout_url' => 'https://checkout.test/'.Str::random(12),
            'metadata' => null,
        ];
    }

    /**
     * Build a unique, human-friendly payment reference.
     */
    private function reference(): string
    {
        return 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)).fake()->unique()->numberBetween(1000, 9999);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Failed,
            'failed_at' => now(),
        ]);
    }
}
