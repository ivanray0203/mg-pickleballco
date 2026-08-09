<?php

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\Court;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use App\Notifications\OpenPlaySessionReminder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create();
});

/**
 * A session tomorrow with one confirmed registrant.
 */
function sessionWithRegistrant(User $customer, Court $court, array $overrides = []): OpenPlaySession
{
    $session = OpenPlaySession::factory()->for($court)->create(array_merge([
        'status' => OpenPlaySessionStatus::Open,
        'session_date' => now()->addDay()->toDateString(),
        'reminder_sent_at' => null,
    ], $overrides));

    OpenPlayRegistration::factory()
        ->for($session, 'session')
        ->for($customer, 'user')
        ->create(['status' => RegistrationStatus::Confirmed]);

    return $session;
}

test('a reminder is sent to registrants of sessions within the next day', function () {
    Notification::fake();

    $session = sessionWithRegistrant($this->customer, $this->court);

    $this->artisan('open-play:send-reminders')->assertSuccessful();

    Notification::assertSentTo($this->customer, OpenPlaySessionReminder::class);
    expect($session->fresh()->reminder_sent_at)->not->toBeNull();
});

test('a session is never reminded twice', function () {
    Notification::fake();

    sessionWithRegistrant($this->customer, $this->court, ['reminder_sent_at' => now()]);

    $this->artisan('open-play:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

test('a reminder is not sent for sessions outside the reminder window', function () {
    Notification::fake();

    sessionWithRegistrant($this->customer, $this->court, [
        'session_date' => now()->addDays(5)->toDateString(),
    ]);

    $this->artisan('open-play:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

test('a reminder is not sent for cancelled or completed sessions', function () {
    Notification::fake();

    sessionWithRegistrant($this->customer, $this->court, [
        'status' => OpenPlaySessionStatus::Cancelled,
    ]);
    sessionWithRegistrant($this->customer, $this->court, [
        'status' => OpenPlaySessionStatus::Completed,
    ]);

    $this->artisan('open-play:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

test('reserved (unpaid) registrations are not reminded', function () {
    Notification::fake();

    $session = OpenPlaySession::factory()->for($this->court)->create([
        'status' => OpenPlaySessionStatus::Open,
        'session_date' => now()->addDay()->toDateString(),
        'reminder_sent_at' => null,
    ]);
    OpenPlayRegistration::factory()
        ->for($session, 'session')
        ->for($this->customer, 'user')
        ->create(['status' => RegistrationStatus::Reserved]);

    $this->artisan('open-play:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});
