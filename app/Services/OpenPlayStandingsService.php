<?php

namespace App\Services;

use App\Models\OpenPlayGame;
use App\Models\OpenPlayGamePlayer;
use App\Models\OpenPlaySession;
use Illuminate\Support\Collection;

/**
 * Computes per-session standings on read from recorded games. Standings reset
 * each session (no cross-session accumulation in the MVP), so there is no
 * stored standings table — everything is derived from `open_play_games` and
 * `open_play_game_players`.
 */
class OpenPlayStandingsService
{
    /**
     * Ranked standings rows for a session, best first.
     *
     * Ordering / tiebreakers: wins DESC → point differential DESC →
     * points for DESC → player name ASC. Ranks are positional (1..n).
     *
     * @return Collection<int, array{
     *     rank: int,
     *     registration_id: int,
     *     name: string,
     *     wins: int,
     *     losses: int,
     *     games_played: int,
     *     points_for: int,
     *     points_against: int,
     *     point_diff: int,
     * }>
     */
    public function forSession(OpenPlaySession $session): Collection
    {
        $games = $session->games()->with('players.registration.user')->get();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = [];

        foreach ($games as $game) {
            foreach ($game->players as $player) {
                $this->applyGame($rows, $game, $player);
            }
        }

        return collect($rows)
            ->sortBy([
                ['wins', 'desc'],
                ['point_diff', 'desc'],
                ['points_for', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->map(fn (array $row, int $index): array => [
                'rank' => $index + 1,
                'registration_id' => (int) $row['registration_id'],
                'name' => (string) $row['name'],
                'wins' => (int) $row['wins'],
                'losses' => (int) $row['losses'],
                'games_played' => (int) $row['games_played'],
                'points_for' => (int) $row['points_for'],
                'points_against' => (int) $row['points_against'],
                'point_diff' => (int) $row['point_diff'],
            ]);
    }

    /**
     * Fold a single game/player pairing into the running per-player tallies.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function applyGame(array &$rows, OpenPlayGame $game, OpenPlayGamePlayer $player): void
    {
        $registrationId = $player->open_play_registration_id;
        $isTeamA = $player->team === 'a';
        $pointsFor = $isTeamA ? $game->team_a_score : $game->team_b_score;
        $pointsAgainst = $isTeamA ? $game->team_b_score : $game->team_a_score;
        $won = $game->winning_team === $player->team;

        $row = $rows[$registrationId] ?? [
            'registration_id' => $registrationId,
            'name' => $player->registration->user->name,
            'wins' => 0,
            'losses' => 0,
            'games_played' => 0,
            'points_for' => 0,
            'points_against' => 0,
            'point_diff' => 0,
        ];

        $row['games_played']++;
        $row['points_for'] += $pointsFor;
        $row['points_against'] += $pointsAgainst;
        $row['point_diff'] = $row['points_for'] - $row['points_against'];

        if ($won) {
            $row['wins']++;
        } else {
            $row['losses']++;
        }

        $rows[$registrationId] = $row;
    }
}
