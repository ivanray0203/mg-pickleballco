<?php

use App\Enums\OpenPlaySessionStatus;
use App\Enums\SkillLevel;
use App\Models\Booking;
use App\Models\Court;
use App\Models\OpenPlaySession;
use App\Models\User;
use App\Services\CourtAvailabilityService;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create();
    $this->date = Carbon::tomorrow()->toDateString();
});

/**
 * @return array<string, mixed>
 */
function validSessionPayload(Court $court, string $date, array $overrides = []): array
{
    return array_merge([
        'court_id' => $court->id,
        'title' => 'Evening Open Play',
        'session_date' => $date,
        'start_time' => '18:00',
        'end_time' => '20:00',
        'capacity' => 12,
        'court_count' => 2,
        'skill_level' => SkillLevel::Novice->value,
        'price' => 200,
        'notes' => null,
    ], $overrides);
}

test('a customer cannot view the admin sessions list', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.open-play.index'))
        ->assertForbidden();
});

test('an admin sees the sessions list', function () {
    OpenPlaySession::factory()->for($this->court)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.open-play.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/open-play/index')
            ->has('sessions.data', 1)
        );
});

test('an admin can create a session and price is stored as cents', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.open-play.store'), validSessionPayload($this->court, $this->date))
        ->assertRedirect(route('admin.open-play.index'));

    $session = OpenPlaySession::first();

    expect($session)->not->toBeNull()
        ->and($session->price_amount)->toBe(20000)
        ->and($session->status)->toBe(OpenPlaySessionStatus::Open)
        ->and($session->skill_level)->toBe(SkillLevel::Novice);
});

test('a customer cannot create a session', function () {
    $this->actingAs($this->customer)
        ->post(route('admin.open-play.store'), validSessionPayload($this->court, $this->date))
        ->assertForbidden();

    expect(OpenPlaySession::count())->toBe(0);
});

test('a session cannot be created overlapping an existing booking', function () {
    Booking::factory()->forSlot($this->court, $this->date, 18, 19)->for($this->customer)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.store'), validSessionPayload($this->court, $this->date))
        ->assertSessionHasErrors('start_time');

    expect(OpenPlaySession::count())->toBe(0);
});

test('a session cannot be created overlapping another session', function () {
    OpenPlaySession::factory()->forSlot($this->court, $this->date, 17, 19)->create();

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.store'), validSessionPayload($this->court, $this->date))
        ->assertSessionHasErrors('start_time');

    expect(OpenPlaySession::count())->toBe(1);
});

test('a session cannot be created on an inactive court', function () {
    $inactiveCourt = Court::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.store'), validSessionPayload($inactiveCourt, $this->date))
        ->assertSessionHasErrors('court_id');

    expect(OpenPlaySession::count())->toBe(0);
});

test('a session cannot be created on a day the court is closed', function () {
    // Mark the session date's weekday closed for this court.
    $weekday = Carbon::parse($this->date)->dayOfWeek;
    $this->court->schedules()->create([
        'day_of_week' => $weekday,
        'is_open' => false,
        'opening_time' => '08:00',
        'closing_time' => '22:00',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.store'), validSessionPayload($this->court, $this->date))
        ->assertSessionHasErrors('start_time');

    expect(OpenPlaySession::count())->toBe(0);
});

test('a session cannot be created outside the courts operating hours', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.open-play.store'), validSessionPayload($this->court, $this->date, [
            'start_time' => '06:00',
            'end_time' => '07:00',
        ]))
        ->assertSessionHasErrors('start_time');
});

test('an admin can update a session', function () {
    $session = OpenPlaySession::factory()->forSlot($this->court, $this->date, 18, 20)->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.update', $session), validSessionPayload($this->court, $this->date, [
            'title' => 'Updated Title',
            'capacity' => 16,
        ]))
        ->assertRedirect(route('admin.open-play.index'));

    expect($session->fresh()->title)->toBe('Updated Title')
        ->and($session->fresh()->capacity)->toBe(16);
});

test('an active session blocks a private booking over its window', function () {
    OpenPlaySession::factory()->forSlot($this->court, $this->date, 18, 20)->create();

    $available = app(CourtAvailabilityService::class)
        ->isBookable($this->court, Carbon::parse($this->date), '18:00', '19:00');

    expect($available)->toBeFalse();
});

test('the daily schedule board shows an open play session', function () {
    OpenPlaySession::factory()->forSlot($this->court, $this->date, 18, 20)
        ->create(['title' => 'Evening Open Play']);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => $this->date]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/schedule')
            ->where('schedule.courts.0.slots', fn ($slots) => collect($slots)
                ->contains(fn ($slot) => $slot['status'] === 'session'))
        );
});

test('cancelling a session frees its court window', function () {
    $session = OpenPlaySession::factory()->forSlot($this->court, $this->date, 18, 20)->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.cancel', $session))
        ->assertRedirect();

    expect($session->fresh()->status)->toBe(OpenPlaySessionStatus::Cancelled);

    $available = app(CourtAvailabilityService::class)
        ->isBookable($this->court, Carbon::parse($this->date), '18:00', '19:00');

    expect($available)->toBeTrue();
});
