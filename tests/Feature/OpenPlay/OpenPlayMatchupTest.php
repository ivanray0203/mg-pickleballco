<?php

use App\Enums\MatchupStatus;
use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\Court;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayMatchup;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use App\Services\OpenPlayMatchupService;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->session = OpenPlaySession::factory()->for(Court::factory())->create([
        'status' => OpenPlaySessionStatus::InProgress,
        'capacity' => 12,
    ]);
});

/**
 * Create N checked-in registrations on the current session.
 *
 * @return \Illuminate\Support\Collection<int, OpenPlayRegistration>
 */
function checkedInPlayers(OpenPlaySession $session, int $count): \Illuminate\Support\Collection
{
    return collect(range(1, $count))->map(fn () => OpenPlayRegistration::factory()
        ->for($session, 'session')
        ->for(User::factory(), 'user')
        ->create(['status' => RegistrationStatus::CheckedIn]));
}

test('generating shuffles checked-in players into 2v2 matchups with the first playing', function () {
    checkedInPlayers($this->session, 8);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.matchups.generate', $this->session))
        ->assertRedirect();

    $matchups = OpenPlayMatchup::query()->orderBy('position')->get();

    expect($matchups)->toHaveCount(2)
        ->and($matchups->where('status', MatchupStatus::Playing))->toHaveCount(1)
        ->and($matchups->first()->status)->toBe(MatchupStatus::Playing)
        ->and($matchups->last()->status)->toBe(MatchupStatus::Queued);

    // Every matchup is a clean 2v2 and covers 8 distinct players.
    $covered = collect();
    foreach ($matchups as $matchup) {
        expect($matchup->team_a)->toHaveCount(2)
            ->and($matchup->team_b)->toHaveCount(2);
        $covered = $covered->merge($matchup->team_a)->merge($matchup->team_b);
    }
    expect($covered->unique())->toHaveCount(8);
});

test('leftover players who do not fill a group of four sit out', function () {
    checkedInPlayers($this->session, 10);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.matchups.generate', $this->session));

    // 10 players → two full 2v2 matchups (8 players); 2 sit out.
    expect(OpenPlayMatchup::count())->toBe(2);
});

test('generating needs at least four checked-in players', function () {
    checkedInPlayers($this->session, 3);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.matchups.generate', $this->session))
        ->assertSessionHasErrors('session');

    expect(OpenPlayMatchup::count())->toBe(0);
});

test('recording the playing matchup creates a game and promotes the next match', function () {
    checkedInPlayers($this->session, 8);
    app(OpenPlayMatchupService::class)->generate($this->session);

    $playing = OpenPlayMatchup::where('status', MatchupStatus::Playing)->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.matchups.record', $playing), [
            'team_a_score' => 11,
            'team_b_score' => 6,
        ])
        ->assertRedirect();

    $game = OpenPlayGame::first();

    expect($game)->not->toBeNull()
        ->and($game->winning_team)->toBe('a')
        ->and($game->players)->toHaveCount(4)
        ->and($playing->fresh()->status)->toBe(MatchupStatus::Done)
        ->and($playing->fresh()->open_play_game_id)->toBe($game->id)
        // The remaining queued match is now playing.
        ->and(OpenPlayMatchup::where('status', MatchupStatus::Playing)->count())->toBe(1);
});

test('a queued matchup cannot be recorded directly', function () {
    checkedInPlayers($this->session, 8);
    app(OpenPlayMatchupService::class)->generate($this->session);

    $queued = OpenPlayMatchup::where('status', MatchupStatus::Queued)->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.matchups.record', $queued), [
            'team_a_score' => 11,
            'team_b_score' => 6,
        ])
        ->assertSessionHasErrors('matchup');

    expect(OpenPlayGame::count())->toBe(0);
});

test('a tied score is rejected and the matchup stays playing', function () {
    checkedInPlayers($this->session, 4);
    app(OpenPlayMatchupService::class)->generate($this->session);

    $playing = OpenPlayMatchup::where('status', MatchupStatus::Playing)->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.matchups.record', $playing), [
            'team_a_score' => 9,
            'team_b_score' => 9,
        ])
        ->assertSessionHasErrors('team_b_score');

    expect(OpenPlayGame::count())->toBe(0)
        ->and($playing->fresh()->status)->toBe(MatchupStatus::Playing);
});

test('removing the playing matchup promotes the next queued one', function () {
    checkedInPlayers($this->session, 8);
    app(OpenPlayMatchupService::class)->generate($this->session);

    $playing = OpenPlayMatchup::where('status', MatchupStatus::Playing)->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('admin.open-play.matchups.destroy', $playing))
        ->assertRedirect();

    expect(OpenPlayMatchup::count())->toBe(1)
        ->and(OpenPlayMatchup::where('status', MatchupStatus::Playing)->count())->toBe(1);
});

test('clearing the queue removes all un-played matchups', function () {
    checkedInPlayers($this->session, 8);
    app(OpenPlayMatchupService::class)->generate($this->session);

    $this->actingAs($this->admin)
        ->delete(route('admin.open-play.matchups.clear', $this->session))
        ->assertRedirect();

    expect(OpenPlayMatchup::count())->toBe(0);
});

test('a completed session cannot generate matchups', function () {
    checkedInPlayers($this->session, 4);
    $this->session->update(['status' => OpenPlaySessionStatus::Completed]);

    $this->actingAs($this->admin)
        ->post(route('admin.open-play.matchups.generate', $this->session))
        ->assertSessionHasErrors('session');

    expect(OpenPlayMatchup::count())->toBe(0);
});

test('a customer cannot generate matchups', function () {
    checkedInPlayers($this->session, 4);

    $this->actingAs($this->customer)
        ->post(route('admin.open-play.matchups.generate', $this->session))
        ->assertForbidden();

    expect(OpenPlayMatchup::count())->toBe(0);
});

test('a multi-court session fills every court and queues the rest', function () {
    $session = OpenPlaySession::factory()->for(Court::factory())->create([
        'status' => OpenPlaySessionStatus::InProgress,
        'capacity' => 28,
        'court_count' => 4,
    ]);
    checkedInPlayers($session, 24);

    app(OpenPlayMatchupService::class)->generate($session);

    $matchups = OpenPlayMatchup::where('open_play_session_id', $session->id)->get();
    $playing = $matchups->where('status', MatchupStatus::Playing);

    // 24 players → six 2v2 matches; four playing (one per court), two queued.
    expect($matchups)->toHaveCount(6)
        ->and($playing)->toHaveCount(4)
        ->and($playing->pluck('court_label')->filter()->unique())->toHaveCount(4)
        ->and($matchups->where('status', MatchupStatus::Queued))->toHaveCount(2);
});

test('finishing a match on one court pulls the next queued match on', function () {
    $session = OpenPlaySession::factory()->for(Court::factory())->create([
        'status' => OpenPlaySessionStatus::InProgress,
        'capacity' => 28,
        'court_count' => 4,
    ]);
    checkedInPlayers($session, 24);
    app(OpenPlayMatchupService::class)->generate($session);

    $playing = OpenPlayMatchup::where('open_play_session_id', $session->id)
        ->where('status', MatchupStatus::Playing)->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.matchups.record', $playing), [
            'team_a_score' => 11,
            'team_b_score' => 6,
        ])
        ->assertRedirect();

    $all = OpenPlayMatchup::where('open_play_session_id', $session->id)->get();

    expect($all->where('status', MatchupStatus::Playing))->toHaveCount(4)
        ->and($all->where('status', MatchupStatus::Done))->toHaveCount(1)
        ->and($all->where('status', MatchupStatus::Queued))->toHaveCount(1);
});

test('an admin can move a playing match to a free court but cannot double-book one', function () {
    $session = OpenPlaySession::factory()->for(Court::factory())->create([
        'status' => OpenPlaySessionStatus::InProgress,
        'capacity' => 16,
        'court_count' => 4,
    ]);
    checkedInPlayers($session, 8);
    app(OpenPlayMatchupService::class)->generate($session);

    $onCourt1 = OpenPlayMatchup::where('open_play_session_id', $session->id)
        ->where('court_label', 'Court 1')->firstOrFail();
    $onCourt2 = OpenPlayMatchup::where('open_play_session_id', $session->id)
        ->where('court_label', 'Court 2')->firstOrFail();

    // Move the first match to a free court.
    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.matchups.court', $onCourt1), ['court_label' => 'Court 3'])
        ->assertRedirect();
    expect($onCourt1->fresh()->court_label)->toBe('Court 3');

    // Cannot move the second match onto an occupied court.
    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.matchups.court', $onCourt2), ['court_label' => 'Court 3'])
        ->assertSessionHasErrors('court');
    expect($onCourt2->fresh()->court_label)->toBe('Court 2');
});

test('the manage page includes the match queue', function () {
    checkedInPlayers($this->session, 4);
    app(OpenPlayMatchupService::class)->generate($this->session);

    $this->actingAs($this->admin)
        ->get(route('admin.open-play.manage', $this->session))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/open-play/manage')
            ->has('matchups', 1)
        );
});
