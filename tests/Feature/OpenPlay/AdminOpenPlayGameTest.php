<?php

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\Court;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->session = OpenPlaySession::factory()
        ->for(Court::factory())
        ->create([
            'status' => OpenPlaySessionStatus::Open,
            'capacity' => 8,
        ]);
});

/**
 * A confirmed registration on the current session for a fresh user.
 */
function confirmedRegistration(OpenPlaySession $session): OpenPlayRegistration
{
    return OpenPlayRegistration::factory()
        ->for($session, 'session')
        ->for(User::factory())
        ->create(['status' => RegistrationStatus::Confirmed]);
}

test('a customer cannot open the manage console', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.open-play.manage', $this->session))
        ->assertForbidden();
});

test('an admin sees the manage console with roster and games', function () {
    confirmedRegistration($this->session);

    $this->actingAs($this->admin)
        ->get(route('admin.open-play.manage', $this->session))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/open-play/manage')
            ->has('registrations.data', 1)
            ->has('games.data', 0)
        );
});

test('the manage console pages the game log under its own page key', function () {
    $a = confirmedRegistration($this->session);
    $b = confirmedRegistration($this->session);

    foreach (range(1, 17) as $index) {
        $game = OpenPlayGame::factory()->for($this->session, 'session')->create([
            'team_a_score' => 11,
            'team_b_score' => $index % 10,
            'winning_team' => 'a',
        ]);

        $game->players()->create(['open_play_registration_id' => $a->id, 'team' => 'a']);
        $game->players()->create(['open_play_registration_id' => $b->id, 'team' => 'b']);
    }

    $this->actingAs($this->admin)
        ->get(route('admin.open-play.manage', $this->session))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('games.data', 15)
            ->where('games.meta.total', 17)
            // The roster is never paged: the recording form needs every player.
            ->has('registrations.data', 2)
        );

    $this->actingAs($this->admin)
        ->get(route('admin.open-play.manage', [$this->session, 'games_page' => 2]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('games.data', 2));
});

test('an admin can check a player in and undo it', function () {
    $registration = confirmedRegistration($this->session);

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.registrations.check-in', $registration))
        ->assertRedirect();

    $registration->refresh();
    expect($registration->status)->toBe(RegistrationStatus::CheckedIn)
        ->and($registration->checked_in_at)->not->toBeNull();

    $this->actingAs($this->admin)
        ->delete(route('admin.open-play.registrations.undo-check-in', $registration))
        ->assertRedirect();

    $registration->refresh();
    expect($registration->status)->toBe(RegistrationStatus::Confirmed)
        ->and($registration->checked_in_at)->toBeNull();
});

test('an admin can record a 2v2 game and the session moves in progress', function () {
    $players = collect(range(1, 4))->map(fn () => confirmedRegistration($this->session));

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.games.store', $this->session), [
            'team_a' => [$players[0]->id, $players[1]->id],
            'team_b' => [$players[2]->id, $players[3]->id],
            'team_a_score' => 11,
            'team_b_score' => 7,
        ])
        ->assertRedirect();

    $game = OpenPlayGame::first();

    expect($game)->not->toBeNull()
        ->and($game->winning_team)->toBe('a')
        ->and($game->players)->toHaveCount(4)
        ->and($this->session->refresh()->status)->toBe(OpenPlaySessionStatus::InProgress);
});

test('an admin can record a 1v1 game', function () {
    $a = confirmedRegistration($this->session);
    $b = confirmedRegistration($this->session);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.games.store', $this->session), [
            'team_a' => [$a->id],
            'team_b' => [$b->id],
            'team_a_score' => 6,
            'team_b_score' => 11,
        ])
        ->assertRedirect();

    expect(OpenPlayGame::first()->winning_team)->toBe('b');
});

test('a tie is rejected', function () {
    $a = confirmedRegistration($this->session);
    $b = confirmedRegistration($this->session);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.games.store', $this->session), [
            'team_a' => [$a->id],
            'team_b' => [$b->id],
            'team_a_score' => 9,
            'team_b_score' => 9,
        ])
        ->assertSessionHasErrors('team_b_score');

    expect(OpenPlayGame::count())->toBe(0);
});

test('a game with a player not in the session is rejected', function () {
    $a = confirmedRegistration($this->session);
    $outsider = OpenPlayRegistration::factory()
        ->for(OpenPlaySession::factory()->for(Court::factory()), 'session')
        ->for(User::factory())
        ->create(['status' => RegistrationStatus::Confirmed]);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.games.store', $this->session), [
            'team_a' => [$a->id],
            'team_b' => [$outsider->id],
            'team_a_score' => 11,
            'team_b_score' => 3,
        ])
        ->assertSessionHasErrors('team_a');

    expect(OpenPlayGame::count())->toBe(0);
});

test('uneven team sizes are rejected', function () {
    $players = collect(range(1, 3))->map(fn () => confirmedRegistration($this->session));

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.games.store', $this->session), [
            'team_a' => [$players[0]->id, $players[1]->id],
            'team_b' => [$players[2]->id],
            'team_a_score' => 11,
            'team_b_score' => 4,
        ])
        ->assertSessionHasErrors('team_a');

    expect(OpenPlayGame::count())->toBe(0);
});

test('a player cannot be on both teams', function () {
    $a = confirmedRegistration($this->session);
    $b = confirmedRegistration($this->session);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.games.store', $this->session), [
            'team_a' => [$a->id],
            'team_b' => [$a->id, $b->id],
            'team_a_score' => 11,
            'team_b_score' => 4,
        ])
        ->assertSessionHasErrors();

    expect(OpenPlayGame::count())->toBe(0);
});

test('an admin can delete a recorded game', function () {
    $a = confirmedRegistration($this->session);
    $b = confirmedRegistration($this->session);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.games.store', $this->session), [
            'team_a' => [$a->id],
            'team_b' => [$b->id],
            'team_a_score' => 11,
            'team_b_score' => 4,
        ]);

    $game = OpenPlayGame::firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('admin.open-play.games.destroy', $game))
        ->assertRedirect();

    expect(OpenPlayGame::count())->toBe(0)
        ->and($game->players()->count())->toBe(0);
});

test('completing a session locks game recording', function () {
    $a = confirmedRegistration($this->session);
    $b = confirmedRegistration($this->session);

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.complete', $this->session))
        ->assertRedirect();

    expect($this->session->refresh()->status)->toBe(OpenPlaySessionStatus::Completed);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.games.store', $this->session), [
            'team_a' => [$a->id],
            'team_b' => [$b->id],
            'team_a_score' => 11,
            'team_b_score' => 4,
        ])
        ->assertSessionHasErrors('session');

    expect(OpenPlayGame::count())->toBe(0);
});

test('a customer cannot record a game', function () {
    $a = confirmedRegistration($this->session);
    $b = confirmedRegistration($this->session);

    $this->actingAs($this->customer)
        ->post(route('admin.open-play.games.store', $this->session), [
            'team_a' => [$a->id],
            'team_b' => [$b->id],
            'team_a_score' => 11,
            'team_b_score' => 4,
        ])
        ->assertForbidden();

    expect(OpenPlayGame::count())->toBe(0);
});
