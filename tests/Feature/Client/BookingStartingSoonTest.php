<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Notifications\BookingStartingSoon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create();
    $this->travelTo(Carbon::parse('2026-08-02 10:00:00'));
});

function startingSoonBooking(array $overrides = []): Booking
{
    return Booking::factory()
        ->for(test()->customer)
        ->for(test()->court)
        ->create(array_merge([
            'booking_date' => '2026-08-02',
            'start_time' => '10:05:00',
            'end_time' => '11:05:00',
            'duration_hours' => 1,
            'starting_soon_sent_at' => null,
        ], $overrides));
}

test('an alert is sent when a confirmed booking is within the lead window', function () {
    Notification::fake();

    $booking = startingSoonBooking();

    $this->artisan('bookings:send-starting-soon')->assertSuccessful();

    Notification::assertSentTo($this->customer, BookingStartingSoon::class);
    expect($booking->fresh()->starting_soon_sent_at)->not->toBeNull();
});

test('a booking is never alerted twice', function () {
    Notification::fake();

    startingSoonBooking(['starting_soon_sent_at' => now()]);

    $this->artisan('bookings:send-starting-soon')->assertSuccessful();

    Notification::assertNothingSent();
});

test('no alert is sent before the lead window opens', function () {
    Notification::fake();

    // Starts at 10:30, so at 10:00 it is still outside the 10-minute window.
    startingSoonBooking(['start_time' => '10:30:00', 'end_time' => '11:30:00']);

    $this->artisan('bookings:send-starting-soon')->assertSuccessful();

    Notification::assertNothingSent();
});

test('no alert is sent for pending or cancelled bookings', function () {
    Notification::fake();

    Booking::factory()->pending()->for($this->customer)->for($this->court)
        ->create(['booking_date' => '2026-08-02', 'start_time' => '10:05:00', 'end_time' => '11:05:00', 'starting_soon_sent_at' => null]);
    Booking::factory()->cancelled()->for($this->customer)->for($this->court)
        ->create(['booking_date' => '2026-08-02', 'start_time' => '10:05:00', 'end_time' => '11:05:00', 'starting_soon_sent_at' => null]);

    $this->artisan('bookings:send-starting-soon')->assertSuccessful();

    Notification::assertNothingSent();
});
