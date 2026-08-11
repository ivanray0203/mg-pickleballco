<?php

use App\Enums\BookingStatus;
use App\Enums\CourtBlockReason;
use App\Enums\DayOfWeek;
use App\Enums\OpenPlaySessionStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\OpenPlaySession;
use App\Models\User;
use App\Services\BookingSelectionService;
use App\Settings\AppSettings;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create([
        'name' => 'Center Court',
        'hourly_rate' => 40000, // ₱400.00
        'opening_time' => '06:00',
        'closing_time' => '22:00',
    ]);

    // Open every day 06:00–22:00.
    foreach (DayOfWeek::cases() as $day) {
        CourtSchedule::factory()->for($this->court)->create([
            'day_of_week' => $day,
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_open' => true,
        ]);
    }

    $this->date = Carbon::today()->addDays(3);
    $this->selection = app(BookingSelectionService::class);
});

test('slots are whole-hour and available by default', function () {
    $result = $this->selection->forCourtAndDate($this->court, $this->date);

    expect($result['slots'])->not->toBeEmpty()
        ->and(collect($result['slots'])->every(fn ($s) => str_ends_with($s['time'], ':00')))->toBeTrue()
        ->and(collect($result['slots'])->firstWhere('time', '06:00')['available'])->toBeTrue();
});

test('past dates are not bookable', function () {
    $result = $this->selection->forCourtAndDate($this->court, Carbon::today()->subDay());

    expect($result['bookable_date'])->toBeFalse()
        ->and($result['slots'])->toBeEmpty();
});

test('hours that have already started today are not selectable', function () {
    Carbon::setTestNow(Carbon::today()->setTime(15, 30));

    $slots = collect($this->selection->forCourtAndDate($this->court, Carbon::today())['slots'])->keyBy('time');

    expect($slots['14:00']['available'])->toBeFalse()
        ->and($slots['15:00']['available'])->toBeFalse()
        ->and($slots['16:00']['available'])->toBeTrue();
});

test('an elapsed hour cannot be counted inside a longer block', function () {
    Carbon::setTestNow(Carbon::today()->setTime(15, 30));

    $slots = collect($this->selection->forCourtAndDate($this->court, Carbon::today())['slots'])->keyBy('time');

    expect($slots['14:00']['max_duration'])->toBe(0)
        ->and($slots['16:00']['max_duration'])->toBeGreaterThan(0);
});

test('elapsed hours only affect today', function () {
    Carbon::setTestNow(Carbon::today()->setTime(15, 30));

    $slots = collect($this->selection->forCourtAndDate($this->court, $this->date)['slots'])->keyBy('time');

    expect($slots['06:00']['available'])->toBeTrue()
        ->and($slots['14:00']['available'])->toBeTrue();
});

test('dates beyond the advance limit are not bookable', function () {
    app(AppSettings::class)->update(['advance_booking_days' => 7]);

    $result = $this->selection->forCourtAndDate($this->court, Carbon::today()->addDays(30));

    expect($result['bookable_date'])->toBeFalse()
        ->and($result['slots'])->toBeEmpty();
});

test('a closed day exposes no slots', function () {
    $this->court->schedules()
        ->where('day_of_week', $this->date->dayOfWeek)
        ->update(['is_open' => false, 'opening_time' => null, 'closing_time' => null]);

    $result = $this->selection->forCourtAndDate($this->court, $this->date);

    expect($result['is_open'])->toBeFalse()
        ->and($result['slots'])->toBeEmpty();
});

test('blocked hours are marked unavailable', function () {
    $this->court->blocks()->create([
        'block_date' => $this->date->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'reason' => CourtBlockReason::Maintenance,
        'created_by' => null,
    ]);

    $slots = collect($this->selection->forCourtAndDate($this->court, $this->date)['slots'])->keyBy('time');

    expect($slots['10:00']['available'])->toBeFalse()
        ->and($slots['09:00']['available'])->toBeTrue();
});

test('existing bookings make hours unavailable', function () {
    Booking::factory()->for($this->court)->create([
        'booking_date' => $this->date->toDateString(),
        'start_time' => '08:00',
        'end_time' => '09:00',
        'status' => BookingStatus::Confirmed,
    ]);

    $slots = collect($this->selection->forCourtAndDate($this->court, $this->date)['slots'])->keyBy('time');

    expect($slots['08:00']['available'])->toBeFalse()
        ->and($slots['07:00']['available'])->toBeTrue();
});

test('a booked hour is identified as booked', function () {
    Booking::factory()->for($this->court)->create([
        'booking_date' => $this->date->toDateString(),
        'start_time' => '08:00',
        'end_time' => '09:00',
        'status' => BookingStatus::Confirmed,
    ]);

    $slots = collect($this->selection->forCourtAndDate($this->court, $this->date)['slots'])->keyBy('time');

    expect($slots['08:00']['state'])->toBe('booked')
        ->and($slots['07:00']['state'])->toBe('available');
});

test('an hour held by an open play session is identified as open play', function () {
    OpenPlaySession::factory()->for($this->court)->create([
        'session_date' => $this->date->toDateString(),
        'start_time' => '13:00',
        'end_time' => '15:00',
        'status' => OpenPlaySessionStatus::Open,
    ]);

    $slots = collect($this->selection->forCourtAndDate($this->court, $this->date)['slots'])->keyBy('time');

    expect($slots['13:00']['state'])->toBe('open_play')
        ->and($slots['14:00']['state'])->toBe('open_play')
        ->and($slots['13:00']['available'])->toBeFalse()
        ->and($slots['15:00']['state'])->toBe('available');
});

test('open play is distinguished from a booking and from a court block', function () {
    OpenPlaySession::factory()->for($this->court)->create([
        'session_date' => $this->date->toDateString(),
        'start_time' => '13:00',
        'end_time' => '14:00',
        'status' => OpenPlaySessionStatus::Open,
    ]);

    Booking::factory()->for($this->court)->create([
        'booking_date' => $this->date->toDateString(),
        'start_time' => '16:00',
        'end_time' => '17:00',
        'status' => BookingStatus::Confirmed,
    ]);

    $this->court->blocks()->create([
        'block_date' => $this->date->toDateString(),
        'start_time' => '18:00',
        'end_time' => '19:00',
        'reason' => CourtBlockReason::Maintenance,
        'created_by' => null,
    ]);

    $slots = collect($this->selection->forCourtAndDate($this->court, $this->date)['slots'])->keyBy('time');

    expect($slots['13:00']['state'])->toBe('open_play')
        ->and($slots['16:00']['state'])->toBe('booked')
        ->and($slots['18:00']['state'])->toBe('unavailable');
});

test('a blocked hour is identified without revealing the reason', function () {
    $this->court->blocks()->create([
        'block_date' => $this->date->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'reason' => CourtBlockReason::Maintenance,
        'created_by' => null,
    ]);

    $slots = collect($this->selection->forCourtAndDate($this->court, $this->date)['slots'])->keyBy('time');

    expect($slots['10:00']['state'])->toBe('unavailable')
        ->and($slots['10:00'])->not->toHaveKey('reason');
});

test('an elapsed hour is identified as past even when it was booked', function () {
    Carbon::setTestNow(Carbon::today()->setTime(15, 30));

    Booking::factory()->for($this->court)->create([
        'booking_date' => Carbon::today()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'status' => BookingStatus::Confirmed,
    ]);

    $slots = collect($this->selection->forCourtAndDate($this->court, Carbon::today())['slots'])->keyBy('time');

    expect($slots['10:00']['state'])->toBe('past');
});

test('the hour in progress stays selectable for walk-in bookings', function () {
    Carbon::setTestNow(Carbon::today()->setTime(15, 30));

    $slots = collect(
        $this->selection->forCourtAndDate($this->court, Carbon::today(), includeCurrentHour: true)['slots']
    )->keyBy('time');

    expect($slots['15:00']['available'])->toBeTrue()
        ->and($slots['15:00']['state'])->toBe('available')
        ->and($slots['14:00']['state'])->toBe('past');
});

test('consecutive availability caps the offered duration', function () {
    app(AppSettings::class)->update(['max_booking_hours' => 4]);

    // Block 17:00 so 15:00 can only extend to 1 or 2 hours.
    $this->court->blocks()->create([
        'block_date' => $this->date->toDateString(),
        'start_time' => '17:00',
        'end_time' => '18:00',
        'reason' => CourtBlockReason::Maintenance,
        'created_by' => null,
    ]);

    $slots = collect($this->selection->forCourtAndDate($this->court, $this->date)['slots'])->keyBy('time');

    // 15:00 available, 16:00 available, 17:00 blocked → max 2 hours.
    expect($slots['15:00']['max_duration'])->toBe(2)
        ->and($slots['16:00']['max_duration'])->toBe(1)
        ->and($slots['17:00']['available'])->toBeFalse()
        ->and($slots['17:00']['max_duration'])->toBe(0);
});

test('the maximum booking duration from settings is respected', function () {
    app(AppSettings::class)->update(['max_booking_hours' => 3]);

    // 06:00 has many free hours ahead but should cap at 3.
    $slots = collect($this->selection->forCourtAndDate($this->court, $this->date)['slots'])->keyBy('time');

    expect($slots['06:00']['max_duration'])->toBe(3);
});

test('the booking page loads for a preselected active court', function () {
    $this->actingAs($this->customer)
        ->get(route('book', ['court' => $this->court->slug]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/book')
            ->where('court.data.slug', $this->court->slug)
            ->where('court.data.hourly_rate', 400)
            ->has('availability.slots')
            ->has('constraints')
        );
});

test('the booking page redirects when the court is inactive', function () {
    $inactive = Court::factory()->inactive()->create();

    $this->actingAs($this->customer)
        ->get(route('book', ['court' => $inactive->slug]))
        ->assertRedirect(route('courts.index'));
});

test('the booking page requires a court', function () {
    $this->actingAs($this->customer)
        ->get(route('book'))
        ->assertSessionHasErrors('court');
});

test('a guest cannot access the booking page', function () {
    $this->get(route('book', ['court' => $this->court->slug]))
        ->assertRedirect(route('login'));
});

test('date selection reflects blocks via a partial reload', function () {
    $this->court->blocks()->create([
        'block_date' => $this->date->toDateString(),
        'start_time' => '12:00',
        'end_time' => '13:00',
        'reason' => CourtBlockReason::Cleaning,
        'created_by' => null,
    ]);

    $this->actingAs($this->customer)
        ->get(route('book', ['court' => $this->court->slug, 'date' => $this->date->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('date', $this->date->toDateString())
            ->where('availability.slots', function ($slots) {
                $byTime = collect($slots)->keyBy('time');

                return $byTime['12:00']['available'] === false
                    && $byTime['11:00']['available'] === true;
            })
        );
});
