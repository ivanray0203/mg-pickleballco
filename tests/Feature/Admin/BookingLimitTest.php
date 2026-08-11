<?php

use App\Models\Court;
use App\Models\User;
use App\Services\BookingService;
use App\Settings\AppSettings;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->court = Court::factory()->create([
        'opening_time' => '06:00',
        'closing_time' => '22:00',
    ]);
    $this->user = User::factory()->create();
    $this->service = app(BookingService::class);
});

function limitBookingData(array $overrides = []): array
{
    return array_merge([
        'user_id' => test()->user->id,
        'court_id' => test()->court->id,
        'booking_date' => now()->addDay()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '10:00',
    ], $overrides);
}

test('a booking exceeding the maximum duration is rejected', function () {
    app(AppSettings::class)->update(['max_booking_hours' => 2]);

    expect(fn () => $this->service->create(limitBookingData([
        'start_time' => '08:00',
        'end_time' => '12:00', // 4 hours > 2
    ])))->toThrow(ValidationException::class);
});

test('a booking beyond the advance limit is rejected', function () {
    app(AppSettings::class)->update(['advance_booking_days' => 7]);

    expect(fn () => $this->service->create(limitBookingData([
        'booking_date' => now()->addDays(30)->toDateString(),
    ])))->toThrow(ValidationException::class);
});

test('a booking for an hour that has fully elapsed today is rejected', function () {
    Carbon::setTestNow(Carbon::today()->setTime(15, 30));

    expect(fn () => $this->service->create(limitBookingData([
        'booking_date' => Carbon::today()->toDateString(),
        'start_time' => '14:00',
        'end_time' => '15:00',
    ])))->toThrow(ValidationException::class);
});

test('a walk-in for the hour currently in progress is still allowed', function () {
    Carbon::setTestNow(Carbon::today()->setTime(15, 30));

    $booking = $this->service->create(limitBookingData([
        'booking_date' => Carbon::today()->toDateString(),
        'start_time' => '15:00',
        'end_time' => '16:00',
    ]));

    expect($booking->start_time)->toStartWith('15:00');
});

test('a booking within configured limits succeeds', function () {
    app(AppSettings::class)->update(['max_booking_hours' => 4, 'advance_booking_days' => 30]);

    $booking = $this->service->create(limitBookingData());

    expect($booking->duration_hours)->toBe(2);
});
