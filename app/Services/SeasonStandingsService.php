<?php

namespace App\Services;

use App\Enums\OpenPlaySessionStatus;
use App\Enums\SkillLevel;
use App\Models\OpenPlayGame;
use App\Models\OpenPlayGamePlayer;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * Aggregates per-session open-play results into a season-long leaderboard.
 * Standings are computed on read from the same `open_play_games` data the MVP
 * already records — a season is just a date window over sessions.
 *
 * Rows use the same shape as {@see OpenPlayStandingsService} (so the podium and
 * standings-table components are reused verbatim), but identity is the player
 * (user) across sessions, so `registration_id` carries the user id here.
 */
class SeasonStandingsService
{
    /**
     * Ranked leaderboard rows for a season, best first. Optionally filtered to a
     * single self-rated skill tier (filtering happens on the players before
     * ranking). Ordering: wins DESC → point differential DESC → points for DESC
     * → name ASC. Rows reuse the per-session standing shape.
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
    public function forSeason(Season $season, ?SkillLevel $skillLevel = null): Collection
    {
        $games = OpenPlayGame::query()
            ->whereHas('session', fn ($query) => $query
                ->whereDate('session_date', '>=', $season->starts_on)
                ->whereDate('session_date', '<=', $season->ends_on)
                ->where('status', '!=', OpenPlaySessionStatus::Cancelled->value))
            ->with('players.registration.user')
            ->get();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = [];

        foreach ($games as $game) {
            foreach ($game->players as $player) {
                $this->applyGame($rows, $game, $player);
            }
        }

        if ($skillLevel !== null) {
            $rows = array_filter(
                $rows,
                fn (array $row): bool => $row['skill_level'] === $skillLevel->value,
            );
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
                'registration_id' => (int) $row['user_id'],
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
     * Fold one game/player pairing into the running per-player (per-user) tally.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function applyGame(array &$rows, OpenPlayGame $game, OpenPlayGamePlayer $player): void
    {
        $user = $player->registration->user;
        $userId = $user->id;
        $isTeamA = $player->team === 'a';
        $pointsFor = $isTeamA ? $game->team_a_score : $game->team_b_score;
        $pointsAgainst = $isTeamA ? $game->team_b_score : $game->team_a_score;
        $won = $game->winning_team === $player->team;

        $row = $rows[$userId] ?? [
            'user_id' => $userId,
            'name' => $user->name,
            'skill_level' => $user->skill_level?->value,
            'skill_level_label' => $user->skill_level?->label(),
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

        $rows[$userId] = $row;
    }
}
