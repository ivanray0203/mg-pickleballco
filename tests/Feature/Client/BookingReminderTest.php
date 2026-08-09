<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Notifications\BookingReminder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create();
});

test('a reminder is sent for confirmed bookings within the next day', function () {
    Notification::fake();

    $booking = Booking::factory()
        ->for($this->customer)
        ->for($this->court)
        ->create(['booking_date' => now()->addDay()->toDateString(), 'reminder_sent_at' => null]);

    $this->artisan('bookings:send-reminders')->assertSuccessful();

    Notification::assertSentTo($this->customer, BookingReminder::class);
    expect($booking->fresh()->reminder_sent_at)->not->toBeNull();
});

test('a booking is never reminded twice', function () {
    Notification::fake();

    Booking::factory()
        ->for($this->customer)
        ->for($this->court)
        ->create(['booking_date' => now()->addDay()->toDateString(), 'reminder_sent_at' => now()]);

    $this->artisan('bookings:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

test('a reminder is not sent for bookings outside the reminder window', function () {
    Notification::fake();

    Booking::factory()
        ->for($this->customer)
        ->for($this->court)
        ->create(['booking_date' => now()->addDays(5)->toDateString(), 'reminder_sent_at' => null]);

    $this->artisan('bookings:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

test('a reminder is not sent for pending or cancelled bookings', function () {
    Notification::fake();

    Booking::factory()->pending()->for($this->customer)->for($this->court)
        ->create(['booking_date' => now()->addDay()->toDateString(), 'reminder_sent_at' => null]);
    Booking::factory()->cancelled()->for($this->customer)->for($this->court)
        ->create(['booking_date' => now()->addDay()->toDateString(), 'reminder_sent_at' => null]);

    $this->artisan('bookings:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});
