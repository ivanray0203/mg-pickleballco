<?php

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Models\Court;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\Season;
use App\Models\User;
use App\Services\SeasonStandingsService;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->season = Season::factory()->create([
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-01-31',
        'is_active' => true,
    ]);
});

function seasonSession(string $date, OpenPlaySessionStatus $status = OpenPlaySessionStatus::InProgress): OpenPlaySession
{
    return OpenPlaySession::factory()->for(Court::factory())->create([
        'session_date' => $date,
        'status' => $status,
    ]);
}

function seasonReg(OpenPlaySession $session, User $user): OpenPlayRegistration
{
    return OpenPlayRegistration::factory()
        ->for($session, 'session')
        ->for($user, 'user')
        ->create(['status' => RegistrationStatus::Confirmed]);
}

function seasonGame(OpenPlaySession $session, OpenPlayRegistration $a, int $scoreA, OpenPlayRegistration $b, int $scoreB): OpenPlayGame
{
    $game = OpenPlayGame::factory()->for($session, 'session')->create([
        'team_a_score' => $scoreA,
        'team_b_score' => $scoreB,
        'winning_team' => $scoreA > $scoreB ? 'a' : 'b',
    ]);
    $game->players()->create(['open_play_registration_id' => $a->id, 'team' => 'a']);
    $game->players()->create(['open_play_registration_id' => $b->id, 'team' => 'b']);

    return $game;
}

test('the leaderboard aggregates a player across sessions and ignores games outside the window', function () {
    $ann = User::factory()->create(['name' => 'Ann', 'skill_level' => SkillLevel::Beginner]);
    $bob = User::factory()->create(['name' => 'Bob', 'skill_level' => SkillLevel::Expert]);

    $s1 = seasonSession('2026-01-10');
    $s2 = seasonSession('2026-01-20');
    $outside = seasonSession('2026-02-05');

    seasonGame($s1, seasonReg($s1, $ann), 11, seasonReg($s1, $bob), 4);
    seasonGame($s2, seasonReg($s2, $ann), 11, seasonReg($s2, $bob), 6);
    // Outside the season window — must not count.
    seasonGame($outside, seasonReg($outside, $bob), 11, seasonReg($outside, $ann), 2);

    $standings = app(SeasonStandingsService::class)->forSeason($this->season);

    expect($standings->pluck('name')->all())->toBe(['Ann', 'Bob']);

    $rows = $standings->keyBy('name');
    expect($rows['Ann'])->toMatchArray([
        'rank' => 1, 'wins' => 2, 'losses' => 0, 'games_played' => 2,
        'points_for' => 22, 'points_against' => 10, 'point_diff' => 12,
    ]);
    expect($rows['Bob'])->toMatchArray([
        'rank' => 2, 'wins' => 0, 'losses' => 2, 'games_played' => 2, 'point_diff' => -12,
    ]);
});

test('cancelled sessions are excluded from the leaderboard', function () {
    $ann = User::factory()->create(['name' => 'Ann']);
    $bob = User::factory()->create(['name' => 'Bob']);

    $cancelled = seasonSession('2026-01-15', OpenPlaySessionStatus::Cancelled);
    seasonGame($cancelled, seasonReg($cancelled, $ann), 11, seasonReg($cancelled, $bob), 3);

    expect(app(SeasonStandingsService::class)->forSeason($this->season))->toBeEmpty();
});

test('the leaderboard can be filtered by skill level', function () {
    $ann = User::factory()->create(['name' => 'Ann', 'skill_level' => SkillLevel::Beginner]);
    $bob = User::factory()->create(['name' => 'Bob', 'skill_level' => SkillLevel::Expert]);

    $session = seasonSession('2026-01-10');
    seasonGame($session, seasonReg($session, $ann), 11, seasonReg($session, $bob), 4);

    $beginners = app(SeasonStandingsService::class)
        ->forSeason($this->season, SkillLevel::Beginner);

    expect($beginners->pluck('name')->all())->toBe(['Ann']);
});

test('a customer cannot view the admin seasons list', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.seasons.index'))
        ->assertForbidden();
});

test('an admin can create a season', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.seasons.store'), [
            'name' => 'Spring 2026',
            'starts_on' => '2026-03-01',
            'ends_on' => '2026-05-31',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.seasons.index'));

    expect(Season::where('name', 'Spring 2026')->exists())->toBeTrue();
});

test('a season cannot end before it starts', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.seasons.store'), [
            'name' => 'Broken',
            'starts_on' => '2026-05-31',
            'ends_on' => '2026-03-01',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('ends_on');
});

test('the admin leaderboard page renders standings', function () {
    $ann = User::factory()->create(['name' => 'Ann']);
    $bob = User::factory()->create(['name' => 'Bob']);
    $session = seasonSession('2026-01-10');
    seasonGame($session, seasonReg($session, $ann), 11, seasonReg($session, $bob), 4);

    $this->actingAs($this->admin)
        ->get(route('admin.seasons.leaderboard', $this->season))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/seasons/leaderboard')
            ->has('standings', 2)
        );
});

test('the client leaderboard page renders the active season', function () {
    $this->actingAs($this->customer)
        ->get(route('leaderboard.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/leaderboard')
            ->has('seasons.data', 1)
        );
});
