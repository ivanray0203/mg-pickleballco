<?php

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\Court;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use App\Notifications\OpenPlayResultsPosted;
use App\Notifications\OpenPlaySessionCancelled;
use App\Notifications\OpenPlaySessionReminder;
use App\Notifications\OpenPlayRegistrationConfirmed;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create();
});

test('registering for a free session notifies the customer', function () {
    Notification::fake();

    $session = OpenPlaySession::factory()->for($this->court)->free()->create([
        'status' => OpenPlaySessionStatus::Open,
        'session_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($this->customer)
        ->post(route('open-play.register', $session))
        ->assertRedirect(route('open-play.show', $session));

    Notification::assertSentTo($this->customer, OpenPlayRegistrationConfirmed::class);
});

test('cancelling a session notifies its confirmed registrants', function () {
    Notification::fake();

    $session = OpenPlaySession::factory()->for($this->court)->create([
        'status' => OpenPlaySessionStatus::Open,
    ]);
    OpenPlayRegistration::factory()
        ->for($session, 'session')
        ->for($this->customer, 'user')
        ->create(['status' => RegistrationStatus::Confirmed]);

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.cancel', $session))
        ->assertRedirect();

    Notification::assertSentTo($this->customer, OpenPlaySessionCancelled::class);
});

test('completing a session notifies registrants that results are posted', function () {
    Notification::fake();

    $session = OpenPlaySession::factory()->for($this->court)->create([
        'status' => OpenPlaySessionStatus::InProgress,
    ]);
    OpenPlayRegistration::factory()
        ->for($session, 'session')
        ->for($this->customer, 'user')
        ->create(['status' => RegistrationStatus::CheckedIn]);

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.complete', $session))
        ->assertRedirect();

    Notification::assertSentTo($this->customer, OpenPlayResultsPosted::class);
});

test('open play notifications carry a session link in the notification center', function () {
    $session = OpenPlaySession::factory()->for($this->court)->create();

    // Sync queue writes the database notification immediately.
    $this->customer->notify(new OpenPlaySessionReminder($session));

    $this->actingAs($this->customer)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/notifications')
            ->where('notifications.data.0.session_id', $session->id)
            ->where('notifications.data.0.booking_id', null)
        );
});
