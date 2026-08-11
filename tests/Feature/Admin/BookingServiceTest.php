<?php

use App\Enums\BookingStatus;
use App\Enums\CourtBlockReason;
use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\CourtSchedule;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(BookingService::class);
    $this->user = User::factory()->create();
    // Factory courts have no schedule rows, so availability falls back to the
    // court's base hours (08:00–22:00) — open every day.
    $this->court = Court::factory()->create([
        'opening_time' => '08:00',
        'closing_time' => '22:00',
        'hourly_rate' => 3000,
    ]);
    // Relative so the suite keeps passing as time moves on; bookings in the
    // past are rejected by the domain.
    $this->date = Carbon::today()->addDays(3)->toDateString();
});

/**
 * @return array<string, mixed>
 */
function bookingData(Court $court, User $user, string $date, string $start, string $end): array
{
    return [
        'user_id' => $user->id,
        'court_id' => $court->id,
        'booking_date' => $date,
        'start_time' => $start,
        'end_time' => $end,
    ];
}

test('a booking belongs to a user and a court', function () {
    $booking = $this->service->create(
        bookingData($this->court, $this->user, $this->date, '09:00', '11:00')
    );

    expect($booking->user)->toBeInstanceOf(User::class)
        ->and($booking->court)->toBeInstanceOf(Court::class)
        ->and($this->user->bookings)->toHaveCount(1)
        ->and($this->court->bookings)->toHaveCount(1);
});

test('it snapshots the price and computes the total', function () {
    $booking = $this->service->create(
        bookingData($this->court, $this->user, $this->date, '09:00', '12:00')
    );

    expect($booking->duration_hours)->toBe(3)
        ->and($booking->hourly_rate)->toBe(3000)
        ->and($booking->total_amount)->toBe(9000);

    // A later price change must not affect the historical booking.
    $this->court->update(['hourly_rate' => 9999]);
    expect($booking->fresh()->total_amount)->toBe(9000);
});

test('bookings must start and end on whole hours', function () {
    $this->service->create(
        bookingData($this->court, $this->user, $this->date, '15:30', '16:30')
    );
})->throws(ValidationException::class);

test('the end time must be after the start time', function () {
    $this->service->create(
        bookingData($this->court, $this->user, $this->date, '16:00', '15:00')
    );
})->throws(ValidationException::class);

test('overlapping bookings are rejected', function (string $start, string $end) {
    Booking::factory()->forSlot($this->court, $this->date, 15, 17)->create([
        'status' => BookingStatus::Confirmed,
    ]);

    expect(fn () => $this->service->create(
        bookingData($this->court, $this->user, $this->date, $start, $end)
    ))->toThrow(ValidationException::class);
})->with([
    'exact overlap' => ['15:00', '17:00'],
    'left half' => ['15:00', '16:00'],
    'right half' => ['16:00', '17:00'],
    'straddling start' => ['14:00', '16:00'],
]);

test('adjacent bookings are allowed', function () {
    Booking::factory()->forSlot($this->court, $this->date, 15, 17)->create([
        'status' => BookingStatus::Confirmed,
    ]);

    $before = $this->service->create(
        bookingData($this->court, $this->user, $this->date, '14:00', '15:00')
    );
    $after = $this->service->create(
        bookingData($this->court, $this->user, $this->date, '17:00', '18:00')
    );

    expect($before)->toBeInstanceOf(Booking::class)
        ->and($after)->toBeInstanceOf(Booking::class);
});

test('cancelled bookings do not block the slot', function () {
    Booking::factory()->forSlot($this->court, $this->date, 15, 17)->cancelled()->create();

    $booking = $this->service->create(
        bookingData($this->court, $this->user, $this->date, '15:00', '17:00')
    );

    expect($booking)->toBeInstanceOf(Booking::class);
});

test('an inactive court cannot accept a booking', function () {
    $this->court->update(['is_active' => false]);

    expect(fn () => $this->service->create(
        bookingData($this->court, $this->user, $this->date, '09:00', '10:00')
    ))->toThrow(ValidationException::class);
});

test('blocked hours cannot be booked', function () {
    CourtBlock::factory()->for($this->court)->create([
        'block_date' => $this->date,
        'start_time' => '09:00',
        'end_time' => '11:00',
        'reason' => CourtBlockReason::Maintenance,
    ]);

    expect(fn () => $this->service->create(
        bookingData($this->court, $this->user, $this->date, '09:00', '10:00')
    ))->toThrow(ValidationException::class);
});

test('a closed day cannot be booked', function () {
    CourtSchedule::factory()->for($this->court)->closed()->create([
        'day_of_week' => DayOfWeek::from(Carbon::parse($this->date)->dayOfWeek),
    ]);

    expect(fn () => $this->service->create(
        bookingData($this->court, $this->user, $this->date, '09:00', '10:00')
    ))->toThrow(ValidationException::class);
});

test('hours outside operating hours cannot be booked', function () {
    // Court base hours are 08:00–22:00.
    expect(fn () => $this->service->create(
        bookingData($this->court, $this->user, $this->date, '07:00', '08:00')
    ))->toThrow(ValidationException::class);
});

test('it generates unique, well-formatted references', function () {
    $first = $this->service->create(
        bookingData($this->court, $this->user, $this->date, '09:00', '10:00')
    );
    $second = $this->service->create(
        bookingData($this->court, $this->user, $this->date, '10:00', '11:00')
    );

    expect($first->booking_reference)->toMatch('/^PB-\d{8}-[A-Z0-9]{4}$/')
        ->and($second->booking_reference)->toMatch('/^PB-\d{8}-[A-Z0-9]{4}$/')
        ->and($first->booking_reference)->not->toBe($second->booking_reference);
});

test('an eligible booking can be cancelled', function () {
    $booking = Booking::factory()->for($this->court)->for($this->user)->create([
        'status' => BookingStatus::Confirmed,
    ]);

    $this->service->cancel($booking, 'Customer requested cancellation.');

    $booking->refresh();
    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->cancelled_at)->not->toBeNull()
        ->and($booking->cancellation_reason)->toBe('Customer requested cancellation.');
});

test('a cancelled booking cannot be cancelled again', function () {
    $booking = Booking::factory()->for($this->court)->for($this->user)->cancelled()->create();

    expect(fn () => $this->service->cancel($booking, 'Nope'))
        ->toThrow(ValidationException::class);
});
