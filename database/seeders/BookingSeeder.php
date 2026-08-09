<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a few sample bookings on active courts for the days ahead.
     */
    public function run(): void
    {
        $customer = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            ['name' => 'Sample Customer', 'password' => bcrypt('password')],
        );

        $courts = Court::query()->active()->orderBy('id')->take(2)->get();

        if ($courts->isEmpty()) {
            return;
        }

        $date = now()->addDay()->toDateString();

        // Non-overlapping sample bookings.
        $plans = [
            [$courts[0], 9, 10, BookingStatus::Confirmed],
            [$courts[0], 10, 12, BookingStatus::PendingPayment],
            [$courts->get(1) ?? $courts[0], 14, 15, BookingStatus::Confirmed],
        ];

        foreach ($plans as [$court, $start, $end, $status]) {
            Booking::factory()
                ->for($customer)
                ->forSlot($court, $date, $start, $end)
                ->state([
                    'status' => $status,
                    'confirmed_at' => $status === BookingStatus::Confirmed ? now() : null,
                    'expires_at' => $status === BookingStatus::PendingPayment ? now()->addMinutes(15) : null,
                ])
                ->create();
        }
    }
}
