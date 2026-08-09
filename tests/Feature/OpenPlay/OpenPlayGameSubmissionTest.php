<?php

use App\Enums\OpenPlayGameSubmissionStatus;
use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\Court;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayGameSubmission;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->session = OpenPlaySession::factory()->for(Court::factory())->create([
        'status' => OpenPlaySessionStatus::InProgress,
        'capacity' => 8,
    ]);
});

function submissionReg(OpenPlaySession $session, ?User $user = null): OpenPlayRegistration
{
    return OpenPlayRegistration::factory()
        ->for($session, 'session')
        ->for($user ?? User::factory(), 'user')
        ->create(['status' => RegistrationStatus::CheckedIn]);
}

test('a checked-in player can submit a result, creating a pending submission but no game', function () {
    $me = submissionReg($this->session, $this->customer);
    $opponent = submissionReg($this->session);

    $this->actingAs($this->customer)
        ->post(route('open-play.submissions.store', $this->session), [
            'team_a' => [$me->id],
            'team_b' => [$opponent->id],
            'team_a_score' => 11,
            'team_b_score' => 7,
        ])
        ->assertRedirect(route('open-play.show', $this->session));

    expect(OpenPlayGameSubmission::count())->toBe(1)
        ->and(OpenPlayGameSubmission::first()->status)->toBe(OpenPlayGameSubmissionStatus::Pending)
        ->and(OpenPlayGame::count())->toBe(0);
});

test('a non-participant cannot submit a result', function () {
    $a = submissionReg($this->session);
    $b = submissionReg($this->session);

    $this->actingAs($this->customer)
        ->post(route('open-play.submissions.store', $this->session), [
            'team_a' => [$a->id],
            'team_b' => [$b->id],
            'team_a_score' => 11,
            'team_b_score' => 7,
        ])
        ->assertSessionHasErrors('session');

    expect(OpenPlayGameSubmission::count())->toBe(0);
});

test('a reporter must be one of the players in the reported game', function () {
    submissionReg($this->session, $this->customer);
    $a = submissionReg($this->session);
    $b = submissionReg($this->session);

    $this->actingAs($this->customer)
        ->post(route('open-play.submissions.store', $this->session), [
            'team_a' => [$a->id],
            'team_b' => [$b->id],
            'team_a_score' => 11,
            'team_b_score' => 7,
        ])
        ->assertSessionHasErrors('team_a');

    expect(OpenPlayGameSubmission::count())->toBe(0);
});

test('an admin can confirm a submission, promoting it to a recorded game', function () {
    $me = submissionReg($this->session, $this->customer);
    $opponent = submissionReg($this->session);
    $submission = OpenPlayGameSubmission::factory()->create([
        'open_play_session_id' => $this->session->id,
        'reported_by_user_id' => $this->customer->id,
        'team_a' => [$me->id],
        'team_b' => [$opponent->id],
        'team_a_score' => 11,
        'team_b_score' => 5,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.submissions.confirm', $submission))
        ->assertRedirect();

    $game = OpenPlayGame::first();

    expect($game)->not->toBeNull()
        ->and($game->winning_team)->toBe('a')
        ->and($game->players)->toHaveCount(2)
        ->and($submission->fresh()->status)->toBe(OpenPlayGameSubmissionStatus::Confirmed)
        ->and($submission->fresh()->open_play_game_id)->toBe($game->id);
});

test('confirming an invalid (tied) submission fails and leaves it pending', function () {
    $me = submissionReg($this->session, $this->customer);
    $opponent = submissionReg($this->session);
    $submission = OpenPlayGameSubmission::factory()->create([
        'open_play_session_id' => $this->session->id,
        'reported_by_user_id' => $this->customer->id,
        'team_a' => [$me->id],
        'team_b' => [$opponent->id],
        'team_a_score' => 9,
        'team_b_score' => 9,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.open-play.submissions.confirm', $submission))
        ->assertSessionHasErrors('team_b_score');

    expect(OpenPlayGame::count())->toBe(0)
        ->and($submission->fresh()->status)->toBe(OpenPlayGameSubmissionStatus::Pending);
});

test('an admin can reject a submission without recording a game', function () {
    $submission = OpenPlayGameSubmission::factory()->create([
        'open_play_session_id' => $this->session->id,
        'reported_by_user_id' => $this->customer->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.open-play.submissions.reject', $submission))
        ->assertRedirect();

    expect($submission->fresh()->status)->toBe(OpenPlayGameSubmissionStatus::Rejected)
        ->and(OpenPlayGame::count())->toBe(0);
});

test('a customer cannot confirm a submission', function () {
    $submission = OpenPlayGameSubmission::factory()->create([
        'open_play_session_id' => $this->session->id,
        'reported_by_user_id' => $this->customer->id,
    ]);

    $this->actingAs($this->customer)
        ->patch(route('admin.open-play.submissions.confirm', $submission))
        ->assertForbidden();

    expect($submission->fresh()->status)->toBe(OpenPlayGameSubmissionStatus::Pending);
});

test('the manage page lists pending submissions', function () {
    $me = submissionReg($this->session, $this->customer);
    $opponent = submissionReg($this->session);
    OpenPlayGameSubmission::factory()->create([
        'open_play_session_id' => $this->session->id,
        'reported_by_user_id' => $this->customer->id,
        'team_a' => [$me->id],
        'team_b' => [$opponent->id],
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.open-play.manage', $this->session))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/open-play/manage')
            ->has('submissions', 1)
        );
});
