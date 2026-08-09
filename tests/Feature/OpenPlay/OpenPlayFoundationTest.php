<?php

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayGamePlayer;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;

test('a session factory builds with a court and casts its enums', function () {
    $session = OpenPlaySession::factory()->create();

    expect($session->court)->not->toBeNull()
        ->and($session->status)->toBeInstanceOf(OpenPlaySessionStatus::class)
        ->and($session->price_amount)->toBeInt();
});

test('a full registration graph can be built through the relationships', function () {
    $session = OpenPlaySession::factory()->create(['capacity' => 4]);

    $registrations = OpenPlayRegistration::factory()
        ->count(2)
        ->for($session, 'session')
        ->create();

    $game = OpenPlayGame::factory()->for($session, 'session')->score(11, 7)->create();

    OpenPlayGamePlayer::factory()->for($game, 'game')
        ->for($registrations[0], 'registration')->onTeam('a')->create();
    OpenPlayGamePlayer::factory()->for($game, 'game')
        ->for($registrations[1], 'registration')->onTeam('b')->create();

    expect($session->registrations)->toHaveCount(2)
        ->and($session->games)->toHaveCount(1)
        ->and($game->players)->toHaveCount(2)
        ->and($game->winning_team)->toBe('a')
        ->and($registrations[0]->gamePlayers)->toHaveCount(1);
});

test('occupied slots and remaining capacity reflect registration status', function () {
    $session = OpenPlaySession::factory()->create(['capacity' => 3]);

    OpenPlayRegistration::factory()->count(2)->for($session, 'session')->create();
    OpenPlayRegistration::factory()->for($session, 'session')->cancelled()->create();
    OpenPlayRegistration::factory()->for($session, 'session')->waitlisted()->create();

    expect($session->occupiedSlots())->toBe(2)
        ->and($session->remainingSlots())->toBe(1)
        ->and($session->isFull())->toBeFalse();
});

test('a user self-rates a skill level that casts to the enum', function () {
    $user = User::factory()->create(['skill_level' => SkillLevel::Novice]);

    expect($user->fresh()->skill_level)->toBe(SkillLevel::Novice);
});

test('a session enforces one registration per user', function () {
    $session = OpenPlaySession::factory()->create();
    $user = User::factory()->create();

    OpenPlayRegistration::factory()->for($session, 'session')->for($user)->create();

    expect(fn () => OpenPlayRegistration::factory()->for($session, 'session')->for($user)->create())
        ->toThrow(Illuminate\Database\QueryException::class);
});

test('the session status enum reports slot-blocking and registration rules', function () {
    expect(OpenPlaySessionStatus::Cancelled->blocksSlot())->toBeFalse()
        ->and(OpenPlaySessionStatus::Open->blocksSlot())->toBeTrue()
        ->and(OpenPlaySessionStatus::Open->acceptsRegistrations())->toBeTrue()
        ->and(OpenPlaySessionStatus::Completed->acceptsRegistrations())->toBeFalse()
        ->and(RegistrationStatus::Confirmed->occupiesSlot())->toBeTrue()
        ->and(RegistrationStatus::Cancelled->occupiesSlot())->toBeFalse();
});
