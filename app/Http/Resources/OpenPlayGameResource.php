<?php

namespace App\Http\Resources;

use App\Models\OpenPlayGame;
use App\Models\OpenPlayGamePlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OpenPlayGame
 */
class OpenPlayGameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_a_score' => $this->team_a_score,
            'team_b_score' => $this->team_b_score,
            'winning_team' => $this->winning_team,
            'played_at_label' => $this->played_at?->format('g:i A'),
            'team_a' => $this->playersOnTeam('a'),
            'team_b' => $this->playersOnTeam('b'),
        ];
    }

    /**
     * The players on a given team as lightweight name rows.
     *
     * @return array<int, array{registration_id: int, name: string}>
     */
    private function playersOnTeam(string $team): array
    {
        return $this->players
            ->where('team', $team)
            ->map(fn (OpenPlayGamePlayer $player): array => [
                'registration_id' => $player->open_play_registration_id,
                'name' => $player->registration->user->name,
            ])
            ->values()
            ->all();
    }
}
