<?php

use App\Enums\CourtBlockReason;
use App\Enums\DayOfWeek;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\CourtSchedule;
use App\Services\CourtAvailabilityService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = app(CourtAvailabilityService::class);
    // A fixed Monday to keep weekday-dependent assertions deterministic.
    $this->monday = Carbon::parse('2026-08-03');
});

test('it generates whole-hour slots within operating hours', function () {
    $court = Court::factory()->create();
    CourtSchedule::factory()->for($court)->create([
        'day_of_week' => DayOfWeek::Monday,
        'opening_time' => '06:00',
        'closing_time' => '10:00',
        'is_open' => true,
    ]);

    $result = $this->service->forDate($court, $this->monday);

    expect($result['is_open'])->toBeTrue()
        ->and($result['slots'])->toHaveCount(4);

    $starts = array_column($result['slots'], 'start_time');
    expect($starts)->toBe(['06:00', '07:00', '08:00', '09:00']);

    // Every slot boundary is a whole hour.
    foreach ($result['slots'] as $slot) {
        expect($slot['start_time'])->toMatch('/^\d{2}:00$/')
            ->and($slot['end_time'])->toMatch('/^\d{2}:00$/')
            ->and($slot['status'])->toBe('available');
    }
});

test('a closed day yields no bookable slots', function () {
    $court = Court::factory()->create();
    CourtSchedule::factory()->for($court)->closed()->create([
        'day_of_week' => DayOfWeek::Monday,
    ]);

    $result = $this->service->forDate($court, $this->monday);

    expect($result['is_open'])->toBeFalse()
        ->and($result['slots'])->toBeEmpty();
});

test('different days can have different operating hours', function () {
    $court = Court::factory()->create();
    CourtSchedule::factory()->for($court)->create([
        'day_of_week' => DayOfWeek::Monday,
        'opening_time' => '06:00',
        'closing_time' => '22:00',
    ]);
    CourtSchedule::factory()->for($court)->create([
        'day_of_week' => DayOfWeek::Tuesday,
        'opening_time' => '08:00',
        'closing_time' => '10:00',
    ]);

    $monday = $this->service->forDate($court, $this->monday);
    $tuesday = $this->service->forDate($court, $this->monday->copy()->addDay());

    expect($monday['slots'])->toHaveCount(16)
        ->and($tuesday['slots'])->toHaveCount(2);
});

test('blocked hours are marked unavailable with a reason', function () {
    $court = Court::factory()->create();
    CourtSchedule::factory()->for($court)->create([
        'day_of_week' => DayOfWeek::Monday,
        'opening_time' => '06:00',
        'closing_time' => '11:00',
    ]);

    CourtBlock::factory()->for($court)->create([
        'block_date' => $this->monday->toDateString(),
        'start_time' => '08:00',
        'end_time' => '10:00',
        'reason' => CourtBlockReason::Maintenance,
    ]);

    $result = $this->service->forDate($court, $this->monday);
    $byStart = collect($result['slots'])->keyBy('start_time');

    expect($byStart['06:00']['status'])->toBe('available')
        ->and($byStart['07:00']['status'])->toBe('available')
        ->and($byStart['08:00']['status'])->toBe('blocked')
        ->and($byStart['08:00']['reason'])->toBe('Court Maintenance')
        ->and($byStart['09:00']['status'])->toBe('blocked')
        ->and($byStart['10:00']['status'])->toBe('available');
});

test('an inactive court is never open', function () {
    $court = Court::factory()->inactive()->create();
    CourtSchedule::factory()->for($court)->create([
        'day_of_week' => DayOfWeek::Monday,
        'is_open' => true,
    ]);

    $result = $this->service->forDate($court, $this->monday);

    expect($result['court_active'])->toBeFalse()
        ->and($result['is_open'])->toBeFalse()
        ->and($result['slots'])->toBeEmpty();
});

test('it falls back to the court base hours when no schedule row exists', function () {
    $court = Court::factory()->create([
        'opening_time' => '09:00',
        'closing_time' => '12:00',
    ]);

    $result = $this->service->forDate($court, $this->monday);

    expect($result['is_open'])->toBeTrue()
        ->and($result['slots'])->toHaveCount(3);
});

test('it reports whether a range is within operating hours', function () {
    $court = Court::factory()->create();
    CourtSchedule::factory()->for($court)->create([
        'day_of_week' => DayOfWeek::Monday,
        'opening_time' => '08:00',
        'closing_time' => '18:00',
    ]);

    expect($this->service->isWithinOperatingHours($court, $this->monday, '09:00', '12:00'))->toBeTrue()
        ->and($this->service->isWithinOperatingHours($court, $this->monday, '07:00', '09:00'))->toBeFalse()
        ->and($this->service->isWithinOperatingHours($court, $this->monday, '17:00', '19:00'))->toBeFalse();
});
