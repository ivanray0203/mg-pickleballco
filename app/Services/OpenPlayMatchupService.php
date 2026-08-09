<?php

namespace App\Services;

use App\Enums\MatchupStatus;
use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayMatchup;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Auto-pairing for a session's play queue across one or more courts. A single
 * shuffle fills the whole queue; up to `court_count` matches are "playing" at
 * once (one per court), and finishing a match frees its court for the next
 * queued match. Scoring runs through the normal {@see OpenPlayGameService}.
 */
class OpenPlayMatchupService
{
    public function __construct(private OpenPlayGameService $games) {}

    /**
     * Shuffle the checked-in players into 2v2 matchups and append them to the
     * queue, then fill every open court. Players who don't fill a group of four
     * sit out this round. Returns the number of matchups created.
     */
    public function generate(OpenPlaySession $session): int
    {
        if (in_array($session->status, [OpenPlaySessionStatus::Completed, OpenPlaySessionStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'session' => 'This session is not accepting new matchups.',
            ]);
        }

        $registrationIds = OpenPlayRegistration::query()
            ->where('open_play_session_id', $session->id)
            ->whereIn('status', [RegistrationStatus::Confirmed, RegistrationStatus::CheckedIn])
            ->pluck('id')
            ->shuffle()
            ->all();

        $groups = array_filter(array_chunk($registrationIds, 4), fn (array $group): bool => count($group) === 4);

        if ($groups === []) {
            throw ValidationException::withMessages([
                'session' => 'At least four checked-in players are needed to generate a match.',
            ]);
        }

        return DB::transaction(function () use ($session, $groups): int {
            $position = (int) OpenPlayMatchup::query()
                ->where('open_play_session_id', $session->id)
                ->max('position');

            foreach ($groups as $group) {
                $position++;

                OpenPlayMatchup::create([
                    'open_play_session_id' => $session->id,
                    'team_a' => [$group[0], $group[1]],
                    'team_b' => [$group[2], $group[3]],
                    'position' => $position,
                    'status' => MatchupStatus::Queued,
                ]);
            }

            $this->fillCourts($session);

            return count($groups);
        });
    }

    /**
     * Record the score of a playing matchup, promoting it to a real game,
     * freeing its court, and pulling the next queued match onto it.
     */
    public function recordResult(OpenPlayMatchup $matchup, int $scoreA, int $scoreB): OpenPlayGame
    {
        if ($matchup->status !== MatchupStatus::Playing) {
            throw ValidationException::withMessages([
                'matchup' => 'Only a match that is currently playing can be recorded.',
            ]);
        }

        return DB::transaction(function () use ($matchup, $scoreA, $scoreB): OpenPlayGame {
            $game = $this->games->recordGame(
                $matchup->session,
                $matchup->team_a,
                $matchup->team_b,
                $scoreA,
                $scoreB,
            );

            $matchup->update([
                'status' => MatchupStatus::Done,
                'court_label' => null,
                'open_play_game_id' => $game->id,
            ]);

            $this->fillCourts($matchup->session);

            return $game;
        });
    }

    /**
     * Remove a queued or playing matchup, filling the freed court if any.
     */
    public function cancel(OpenPlayMatchup $matchup): void
    {
        if ($matchup->status === MatchupStatus::Done) {
            throw ValidationException::withMessages([
                'matchup' => 'A completed match cannot be removed.',
            ]);
        }

        $session = $matchup->session;
        $matchup->delete();

        $this->fillCourts($session);
    }

    /**
     * Move a playing matchup to a different (free) court.
     */
    public function assignCourt(OpenPlayMatchup $matchup, string $label): OpenPlayMatchup
    {
        if ($matchup->status !== MatchupStatus::Playing) {
            throw ValidationException::withMessages([
                'court' => 'Only a playing match can be assigned a court.',
            ]);
        }

        $session = $matchup->session;

        if (! in_array($label, $this->courtLabels($session), true)) {
            throw ValidationException::withMessages([
                'court' => 'That court is not part of this session.',
            ]);
        }

        $taken = OpenPlayMatchup::query()
            ->where('open_play_session_id', $session->id)
            ->where('status', MatchupStatus::Playing)
            ->whereKeyNot($matchup->id)
            ->where('court_label', $label)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'court' => 'That court is already in use.',
            ]);
        }

        $matchup->update(['court_label' => $label]);

        return $matchup;
    }

    /**
     * Clear the entire un-played queue for a session (a fresh reshuffle).
     */
    public function clearQueue(OpenPlaySession $session): void
    {
        OpenPlayMatchup::query()
            ->where('open_play_session_id', $session->id)
            ->whereIn('status', [MatchupStatus::Queued, MatchupStatus::Playing])
            ->delete();
    }

    /**
     * Promote queued matchups onto every free court, lowest court number first.
     */
    private function fillCourts(OpenPlaySession $session): void
    {
        $playing = OpenPlayMatchup::query()
            ->where('open_play_session_id', $session->id)
            ->where('status', MatchupStatus::Playing)
            ->get();

        $freeSlots = max(1, $session->court_count) - $playing->count();

        if ($freeSlots <= 0) {
            return;
        }

        $occupied = $playing->pluck('court_label')->filter()->all();
        $available = array_values(array_diff($this->courtLabels($session), $occupied));

        $next = OpenPlayMatchup::query()
            ->where('open_play_session_id', $session->id)
            ->where('status', MatchupStatus::Queued)
            ->orderBy('position')
            ->limit($freeSlots)
            ->get();

        foreach ($next as $index => $matchup) {
            $matchup->update([
                'status' => MatchupStatus::Playing,
                'court_label' => $available[$index] ?? null,
            ]);
        }
    }

    /**
     * The court labels available to a session ("Court 1" … "Court N").
     *
     * @return array<int, string>
     */
    private function courtLabels(OpenPlaySession $session): array
    {
        return array_map(
            fn (int $number): string => "Court {$number}",
            range(1, max(1, $session->court_count)),
        );
    }
}
