<?php

use App\Enums\DayOfWeek;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create();
});

/**
 * Build a full 7-day payload, letting individual days be overridden.
 *
 * @param  array<int, array<string, mixed>>  $overrides  keyed by day_of_week
 * @return array{days: array<int, array<string, mixed>>}
 */
function schedulePayload(array $overrides = []): array
{
    $days = [];

    foreach (range(0, 6) as $dow) {
        $days[] = array_merge([
            'day_of_week' => $dow,
            'is_open' => true,
            'opening_time' => '06:00',
            'closing_time' => '22:00',
        ], $overrides[$dow] ?? []);
    }

    return ['days' => $days];
}

test('admin can view the schedule editor with all seven days', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.courts.schedule.edit', $this->court))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/courts/schedule')
            ->has('schedules.data', 7)
        );
});

test('viewing the editor creates default schedule rows', function () {
    expect($this->court->schedules()->count())->toBe(0);

    $this->actingAs($this->admin)
        ->get(route('admin.courts.schedule.edit', $this->court));

    expect($this->court->schedules()->count())->toBe(7);
});

test('customers cannot manage schedules', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.courts.schedule.edit', $this->court))
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->put(route('admin.courts.schedule.update', $this->court), schedulePayload())
        ->assertForbidden();
});

test('admin can update the weekly schedule', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.courts.schedule.update', $this->court), schedulePayload([
            3 => ['opening_time' => '08:00', 'closing_time' => '20:00'],
            0 => ['is_open' => false, 'opening_time' => null, 'closing_time' => null],
        ]))
        ->assertRedirect(route('admin.courts.schedule.edit', $this->court));

    $wednesday = $this->court->schedules()->where('day_of_week', DayOfWeek::Wednesday)->first();
    $sunday = $this->court->schedules()->where('day_of_week', DayOfWeek::Sunday)->first();

    expect(substr($wednesday->opening_time, 0, 5))->toBe('08:00')
        ->and(substr($wednesday->closing_time, 0, 5))->toBe('20:00')
        ->and($sunday->is_open)->toBeFalse()
        ->and($sunday->opening_time)->toBeNull();
});

test('a day can be marked closed', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.courts.schedule.update', $this->court), schedulePayload([
            2 => ['is_open' => false, 'opening_time' => null, 'closing_time' => null],
        ]))
        ->assertSessionHasNoErrors();

    $tuesday = $this->court->schedules()->where('day_of_week', DayOfWeek::Tuesday)->first();
    expect($tuesday->is_open)->toBeFalse();
});

test('schedule times must be whole hours', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.courts.schedule.update', $this->court), schedulePayload([
            1 => ['opening_time' => '06:30'],
        ]))
        ->assertSessionHasErrors('days.1.opening_time');
});

test('schedule closing time must be after opening time', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.courts.schedule.update', $this->court), schedulePayload([
            1 => ['opening_time' => '20:00', 'closing_time' => '08:00'],
        ]))
        ->assertSessionHasErrors('days.1.closing_time');
});

test('an open day requires operating hours', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.courts.schedule.update', $this->court), schedulePayload([
            1 => ['opening_time' => null, 'closing_time' => null],
        ]))
        ->assertSessionHasErrors(['days.1.opening_time', 'days.1.closing_time']);
});
