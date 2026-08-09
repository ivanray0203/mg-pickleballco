<?php

namespace App\Services;

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Notifications\OpenPlayResultsPosted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns session-day operations: checking players in and recording the games
 * that produce a session's match data (which Phase 4 turns into rankings).
 * Standings are derived from game/player rows, so recording is validated hard
 * and persisted transactionally.
 */
class OpenPlayGameService
{
    /**
     * Registration statuses eligible to be checked in or to play a game.
     *
     * @var array<int, RegistrationStatus>
     */
    private const PLAYABLE_STATUSES = [
        RegistrationStatus::Confirmed,
        RegistrationStatus::CheckedIn,
    ];

    /**
     * Mark a confirmed registration as checked in. Idempotent.
     */
    public function checkIn(OpenPlayRegistration $registration): OpenPlayRegistration
    {
        if (! in_array($registration->status, self::PLAYABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'registration' => 'Only confirmed players can be checked in.',
            ]);
        }

        if ($registration->status !== RegistrationStatus::CheckedIn) {
            $registration->update([
                'status' => RegistrationStatus::CheckedIn,
                'checked_in_at' => Carbon::now(),
            ]);
        }

        return $registration;
    }

    /**
     * Revert a check-in back to a plain confirmed registration. Idempotent.
     */
    public function undoCheckIn(OpenPlayRegistration $registration): OpenPlayRegistration
    {
        if ($registration->status === RegistrationStatus::CheckedIn) {
            $registration->update([
                'status' => RegistrationStatus::Confirmed,
                'checked_in_at' => null,
            ]);
        }

        return $registration;
    }

    /**
     * Record a game for a session. Validates that every player is a
     * confirmed/checked-in registration of this session, that team sizes are a
     * legal 1v1 or 2v2 with no shared players, and that the score is a
     * non-negative, non-tied line. Persists the game and its player rows in a
     * transaction and derives the winning team. The first recorded game moves
     * the session into progress.
     *
     * @param  array<int, int>  $teamA  Registration IDs on team A.
     * @param  array<int, int>  $teamB  Registration IDs on team B.
     */
    public function recordGame(OpenPlaySession $session, array $teamA, array $teamB, int $scoreA, int $scoreB): OpenPlayGame
    {
        if (in_array($session->status, [OpenPlaySessionStatus::Completed, OpenPlaySessionStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'session' => 'Games cannot be recorded for a closed session.',
            ]);
        }

        $teamA = array_values(array_unique(array_map('intval', $teamA)));
        $teamB = array_values(array_unique(array_map('intval', $teamB)));

        if (count($teamA) !== count($teamB) || ! in_array(count($teamA), [1, 2], true)) {
            throw ValidationException::withMessages([
                'team_a' => 'Teams must be an even 1v1 or 2v2 match.',
            ]);
        }

        $overlap = array_intersect($teamA, $teamB);

        if ($overlap !== []) {
            throw ValidationException::withMessages([
                'team_b' => 'A player cannot be on both teams.',
            ]);
        }

        if ($scoreA < 0 || $scoreB < 0) {
            throw ValidationException::withMessages([
                'team_a_score' => 'Scores must be zero or greater.',
            ]);
        }

        if ($scoreA === $scoreB) {
            throw ValidationException::withMessages([
                'team_b_score' => 'A game cannot end in a tie.',
            ]);
        }

        $registrationIds = [...$teamA, ...$teamB];

        $eligibleCount = OpenPlayRegistration::query()
            ->where('open_play_session_id', $session->id)
            ->whereIn('id', $registrationIds)
            ->whereIn('status', RegistrationStatus::occupyingValues())
            ->whereIn('status', array_map(fn (RegistrationStatus $status): string => $status->value, self::PLAYABLE_STATUSES))
            ->count();

        if ($eligibleCount !== count($registrationIds)) {
            throw ValidationException::withMessages([
                'team_a' => 'Every player must be a confirmed or checked-in member of this session.',
            ]);
        }

        return DB::transaction(function () use ($session, $teamA, $teamB, $scoreA, $scoreB) {
            $game = OpenPlayGame::create([
                'open_play_session_id' => $session->id,
                'team_a_score' => $scoreA,
                'team_b_score' => $scoreB,
                'winning_team' => $scoreA > $scoreB ? 'a' : 'b',
                'played_at' => Carbon::now(),
            ]);

            foreach ($teamA as $registrationId) {
                $game->players()->create([
                    'open_play_registration_id' => $registrationId,
                    'team' => 'a',
                ]);
            }

            foreach ($teamB as $registrationId) {
                $game->players()->create([
                    'open_play_registration_id' => $registrationId,
                    'team' => 'b',
                ]);
            }

            if (in_array($session->status, [OpenPlaySessionStatus::Scheduled, OpenPlaySessionStatus::Open, OpenPlaySessionStatus::Full], true)) {
                $session->update(['status' => OpenPlaySessionStatus::InProgress]);
            }

            return $game;
        });
    }

    /**
     * Delete a recorded game (and its player rows via cascade) so an organizer
     * can correct a mistake.
     */
    public function deleteGame(OpenPlayGame $game): void
    {
        $game->delete();
    }

    /**
     * Close a session once all games are recorded. The session becomes
     * read-only for game recording.
     */
    public function complete(OpenPlaySession $session): OpenPlaySession
    {
        if (in_array($session->status, [OpenPlaySessionStatus::Completed, OpenPlaySessionStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'status' => 'This session is already closed.',
            ]);
        }

        $session->update(['status' => OpenPlaySessionStatus::Completed]);

        // Let everyone who played know the final standings are posted.
        $session->registrations()
            ->whereIn('status', RegistrationStatus::occupyingValues())
            ->with('user')
            ->get()
            ->each(fn (OpenPlayRegistration $registration) => $registration->user->notify(new OpenPlayResultsPosted($session)));

        return $session;
    }
}
