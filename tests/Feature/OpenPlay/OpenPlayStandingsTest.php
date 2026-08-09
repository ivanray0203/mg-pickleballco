<?php

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\Court;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use App\Services\OpenPlayStandingsService;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->session = OpenPlaySession::factory()
        ->for(Court::factory())
        ->create(['status' => OpenPlaySessionStatus::InProgress, 'capacity' => 8]);
});

/**
 * A confirmed registration for a named player on the current session.
 */
function player(OpenPlaySession $session, string $name): OpenPlayRegistration
{
    return OpenPlayRegistration::factory()
        ->for($session, 'session')
        ->for(User::factory()->state(['name' => $name]), 'user')
        ->create(['status' => RegistrationStatus::Confirmed]);
}

/**
 * Persist a raw 1v1 game with an explicit score line (bypassing recording
 * validation so any fixture can be built).
 */
function rawGame(
    OpenPlaySession $session,
    OpenPlayRegistration $teamA,
    int $scoreA,
    OpenPlayRegistration $teamB,
    int $scoreB,
): OpenPlayGame {
    $game = OpenPlayGame::factory()->for($session, 'session')->create([
        'team_a_score' => $scoreA,
        'team_b_score' => $scoreB,
        'winning_team' => $scoreA > $scoreB ? 'a' : 'b',
    ]);

    $game->players()->create(['open_play_registration_id' => $teamA->id, 'team' => 'a']);
    $game->players()->create(['open_play_registration_id' => $teamB->id, 'team' => 'b']);

    return $game;
}

test('standings match hand-computed results and order by wins then point diff then points for', function () {
    $a = player($this->session, 'Player A');
    $b = player($this->session, 'Player B');
    $c = player($this->session, 'Player C');
    $d = player($this->session, 'Player D');

    // Single round-robin, scores chosen so the final order is A, D, B, C:
    //  A & D tie on 2 wins (broken by point diff); B & C tie on 1 win and
    //  identical -6 diff (broken by points for: B 22 > C 21).
    rawGame($this->session, $a, 11, $b, 3);
    rawGame($this->session, $a, 11, $c, 4);
    rawGame($this->session, $d, 11, $a, 9);
    rawGame($this->session, $b, 11, $c, 6);
    rawGame($this->session, $d, 11, $b, 8);
    rawGame($this->session, $c, 11, $d, 5);

    $standings = app(OpenPlayStandingsService::class)->forSession($this->session);

    expect($standings->pluck('name')->all())->toBe([
        'Player A',
        'Player D',
        'Player B',
        'Player C',
    ]);

    $rows = $standings->keyBy('name');

    expect($rows['Player A'])->toMatchArray([
        'rank' => 1, 'wins' => 2, 'losses' => 1, 'games_played' => 3,
        'points_for' => 31, 'points_against' => 18, 'point_diff' => 13,
    ]);
    expect($rows['Player D'])->toMatchArray([
        'rank' => 2, 'wins' => 2, 'losses' => 1,
        'points_for' => 27, 'points_against' => 28, 'point_diff' => -1,
    ]);
    expect($rows['Player B'])->toMatchArray([
        'rank' => 3, 'wins' => 1, 'losses' => 2,
        'points_for' => 22, 'point_diff' => -6,
    ]);
    expect($rows['Player C'])->toMatchArray([
        'rank' => 4, 'wins' => 1, 'losses' => 2,
        'points_for' => 21, 'point_diff' => -6,
    ]);
});

test('the final tiebreaker is player name', function () {
    $amy = player($this->session, 'Amy');
    $zoe = player($this->session, 'Zoe');
    $ned = player($this->session, 'Ned');
    $roy = player($this->session, 'Roy');

    // Amy and Zoe are teammates, so their stat lines are identical and only
    // their names can separate them.
    $game = OpenPlayGame::factory()->for($this->session, 'session')->create([
        'team_a_score' => 11,
        'team_b_score' => 5,
        'winning_team' => 'a',
    ]);
    $game->players()->createMany([
        ['open_play_registration_id' => $amy->id, 'team' => 'a'],
        ['open_play_registration_id' => $zoe->id, 'team' => 'a'],
        ['open_play_registration_id' => $ned->id, 'team' => 'b'],
        ['open_play_registration_id' => $roy->id, 'team' => 'b'],
    ]);

    $standings = app(OpenPlayStandingsService::class)->forSession($this->session);

    expect($standings->pluck('name')->all())->toBe(['Amy', 'Zoe', 'Ned', 'Roy']);
});

test('a session with no games has empty standings', function () {
    expect(app(OpenPlayStandingsService::class)->forSession($this->session))->toBeEmpty();
});

test('the client detail page exposes final standings for a completed session', function () {
    $this->session->update(['status' => OpenPlaySessionStatus::Completed]);
    $a = player($this->session, 'Player A');
    $b = player($this->session, 'Player B');
    rawGame($this->session, $a, 11, $b, 5);

    $this->actingAs(User::factory()->create())
        ->get(route('open-play.show', $this->session))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/open-play-detail')
            ->where('standingsState', 'final')
            ->has('standings', 2)
        );
});

test('the admin manage page exposes standings', function () {
    $a = player($this->session, 'Player A');
    $b = player($this->session, 'Player B');
    rawGame($this->session, $a, 11, $b, 5);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.open-play.manage', $this->session))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/open-play/manage')
            ->has('standings', 2)
        );
});
