<?php

namespace App\Services;

use App\Enums\OpenPlayGameSubmissionStatus;
use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayGameSubmission;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Player self-reported game results. A submission is a *proposal* that stores
 * the reported teams and score without touching the authoritative standings.
 * An organizer confirms it — which runs the full {@see OpenPlayGameService}
 * validation and creates the real game — or rejects it.
 */
class OpenPlayGameSubmissionService
{
    public function __construct(private OpenPlayGameService $games) {}

    /**
     * Record a player's proposed result. The reporter must be a
     * confirmed/checked-in member of the session and one of the players in the
     * game. Deep team/score validation is deferred to confirmation so the
     * organizer is the one who sees any correction needed.
     *
     * @param  array<int, int>  $teamA
     * @param  array<int, int>  $teamB
     */
    public function submit(OpenPlaySession $session, User $reporter, array $teamA, array $teamB, int $scoreA, int $scoreB): OpenPlayGameSubmission
    {
        if (in_array($session->status, [OpenPlaySessionStatus::Completed, OpenPlaySessionStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'session' => 'This session is not accepting reported results.',
            ]);
        }

        $reporterRegistration = OpenPlayRegistration::query()
            ->where('open_play_session_id', $session->id)
            ->where('user_id', $reporter->id)
            ->whereIn('status', [RegistrationStatus::Confirmed, RegistrationStatus::CheckedIn])
            ->first();

        if ($reporterRegistration === null) {
            throw ValidationException::withMessages([
                'session' => 'Only checked-in players can report a result.',
            ]);
        }

        $teamA = array_values(array_unique(array_map('intval', $teamA)));
        $teamB = array_values(array_unique(array_map('intval', $teamB)));

        if (! in_array($reporterRegistration->id, [...$teamA, ...$teamB], true)) {
            throw ValidationException::withMessages([
                'team_a' => 'You can only report a game you played in.',
            ]);
        }

        return OpenPlayGameSubmission::create([
            'open_play_session_id' => $session->id,
            'reported_by_user_id' => $reporter->id,
            'team_a' => $teamA,
            'team_b' => $teamB,
            'team_a_score' => $scoreA,
            'team_b_score' => $scoreB,
            'status' => OpenPlayGameSubmissionStatus::Pending,
        ]);
    }

    /**
     * Confirm a pending submission, promoting it to an authoritative game. Full
     * validation runs here via {@see OpenPlayGameService::recordGame()}; if it
     * fails the submission stays pending and the error surfaces to the organizer.
     */
    public function confirm(OpenPlayGameSubmission $submission, User $organizer): OpenPlayGame
    {
        $this->assertPending($submission);

        return DB::transaction(function () use ($submission, $organizer) {
            $game = $this->games->recordGame(
                $submission->session,
                $submission->team_a,
                $submission->team_b,
                $submission->team_a_score,
                $submission->team_b_score,
            );

            $submission->update([
                'status' => OpenPlayGameSubmissionStatus::Confirmed,
                'resolved_by_user_id' => $organizer->id,
                'open_play_game_id' => $game->id,
            ]);

            return $game;
        });
    }

    /**
     * Reject a pending submission without recording a game.
     */
    public function reject(OpenPlayGameSubmission $submission, User $organizer): OpenPlayGameSubmission
    {
        $this->assertPending($submission);

        $submission->update([
            'status' => OpenPlayGameSubmissionStatus::Rejected,
            'resolved_by_user_id' => $organizer->id,
        ]);

        return $submission;
    }

    private function assertPending(OpenPlayGameSubmission $submission): void
    {
        if ($submission->status !== OpenPlayGameSubmissionStatus::Pending) {
            throw ValidationException::withMessages([
                'submission' => 'This submission has already been resolved.',
            ]);
        }
    }
}
