<?php

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->customer = User::factory()->create();
});

/**
 * A free session open for registration tomorrow.
 */
function freeSession(int $capacity = 4): OpenPlaySession
{
    return OpenPlaySession::factory()->free()->create([
        'status' => OpenPlaySessionStatus::Open,
        'session_date' => now()->addDay()->toDateString(),
        'capacity' => $capacity,
    ]);
}

test('a guest is redirected from open play', function () {
    $this->get(route('open-play.index'))->assertRedirect(route('login'));
});

test('the list shows only upcoming sessions open for registration', function () {
    freeSession();
    OpenPlaySession::factory()->create([
        'status' => OpenPlaySessionStatus::Cancelled,
        'session_date' => now()->addDay()->toDateString(),
    ]);
    OpenPlaySession::factory()->completed()->create();

    $this->actingAs($this->customer)
        ->get(route('open-play.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/open-play')
            ->has('sessions.data', 1)
        );
});

test('the list can be filtered by skill level', function () {
    OpenPlaySession::factory()->free()->create([
        'status' => OpenPlaySessionStatus::Open,
        'session_date' => now()->addDay()->toDateString(),
        'skill_level' => SkillLevel::Beginner,
    ]);
    OpenPlaySession::factory()->free()->create([
        'status' => OpenPlaySessionStatus::Open,
        'session_date' => now()->addDay()->toDateString(),
        'skill_level' => SkillLevel::Expert,
    ]);

    $this->actingAs($this->customer)
        ->get(route('open-play.index', ['skill_level' => SkillLevel::Beginner->value]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sessions.data', 1)
            ->where('sessions.data.0.skill_level_label', SkillLevel::Beginner->label())
        );
});

test('a customer can register for a free session and it confirms immediately', function () {
    $session = freeSession();

    $this->actingAs($this->customer)
        ->post(route('open-play.register', $session))
        ->assertRedirect(route('open-play.show', $session));

    $registration = OpenPlayRegistration::first();

    expect($registration->status)->toBe(RegistrationStatus::Confirmed)
        ->and($session->fresh()->occupiedSlots())->toBe(1);
});

test('registering a paid session reserves a hold and starts checkout', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => ['id' => 'cs_test_123', 'attributes' => ['checkout_url' => 'https://checkout.test/pay']],
        ], 200),
    ]);

    config()->set('services.paymongo.secret_key', 'sk_test_123');

    $session = OpenPlaySession::factory()->paid(20000)->create([
        'status' => OpenPlaySessionStatus::Open,
        'session_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($this->customer)
        ->post(route('open-play.register', $session));

    $registration = OpenPlayRegistration::first();

    expect($registration->status)->toBe(RegistrationStatus::Reserved)
        ->and($registration->expires_at)->not->toBeNull();

    $payment = Payment::first();

    expect($payment->open_play_registration_id)->toBe($registration->id)
        ->and($payment->booking_id)->toBeNull()
        ->and($payment->amount)->toBe(20000);
});

test('a reserved hold still occupies a capacity slot', function () {
    $session = OpenPlaySession::factory()->create([
        'status' => OpenPlaySessionStatus::Open,
        'session_date' => now()->addDay()->toDateString(),
        'capacity' => 4,
    ]);

    OpenPlayRegistration::factory()->for($session, 'session')->reserved()->create();

    expect($session->fresh()->occupiedSlots())->toBe(1);
});

test('a session cannot be over-registered beyond capacity', function () {
    $session = freeSession(capacity: 2);

    OpenPlayRegistration::factory()->count(2)->for($session, 'session')->create();

    $this->actingAs($this->customer)
        ->post(route('open-play.register', $session))
        ->assertSessionHasErrors('session');

    expect($session->fresh()->occupiedSlots())->toBe(2);
});

test('a customer cannot register twice for the same session', function () {
    $session = freeSession();

    OpenPlayRegistration::factory()->for($session, 'session')->for($this->customer)->create();

    $this->actingAs($this->customer)
        ->post(route('open-play.register', $session))
        ->assertSessionHasErrors('session');

    expect(OpenPlayRegistration::where('user_id', $this->customer->id)->count())->toBe(1);
});

test('a customer can cancel and then re-register, freeing and retaking the slot', function () {
    $session = freeSession();

    $registration = OpenPlayRegistration::factory()
        ->for($session, 'session')->for($this->customer)->create();

    $this->actingAs($this->customer)
        ->delete(route('open-play.registrations.cancel', $registration))
        ->assertRedirect();

    expect($session->fresh()->occupiedSlots())->toBe(0);

    // Re-register reactivates the same row (one-per-user constraint).
    $this->actingAs($this->customer)
        ->post(route('open-play.register', $session))
        ->assertRedirect();

    expect($session->fresh()->occupiedSlots())->toBe(1)
        ->and(OpenPlayRegistration::where('user_id', $this->customer->id)->count())->toBe(1);
});

test('a customer cannot cancel another customers registration', function () {
    $session = freeSession();
    $other = User::factory()->create();
    $registration = OpenPlayRegistration::factory()
        ->for($session, 'session')->for($other)->create();

    $this->actingAs($this->customer)
        ->delete(route('open-play.registrations.cancel', $registration))
        ->assertForbidden();

    expect($registration->fresh()->status)->toBe(RegistrationStatus::Confirmed);
});

test('a customer cannot register for a cancelled session', function () {
    $session = OpenPlaySession::factory()->create([
        'status' => OpenPlaySessionStatus::Cancelled,
        'session_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($this->customer)
        ->post(route('open-play.register', $session))
        ->assertSessionHasErrors('session');

    expect(OpenPlayRegistration::count())->toBe(0);
});

test('expiring open play holds releases the slot', function () {
    $session = freeSession();

    OpenPlayRegistration::factory()->for($session, 'session')->reserved()->create([
        'expires_at' => now()->subMinute(),
    ]);

    expect($session->fresh()->occupiedSlots())->toBe(1);

    $this->artisan('open-play:expire-holds')->assertSuccessful();

    expect($session->fresh()->occupiedSlots())->toBe(0);
});
