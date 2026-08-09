<?php

namespace App\Http\Controllers\Client;

use App\Enums\SkillLevel;
use App\Http\Controllers\Controller;
use App\Http\Resources\SeasonResource;
use App\Models\Season;
use App\Services\SeasonStandingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function __construct(private SeasonStandingsService $standings) {}

    /**
     * Public season leaderboard: pick an active season and optionally filter by
     * skill tier. Defaults to the most recent active season.
     */
    public function index(Request $request): Response
    {
        $seasons = Season::query()->active()->orderByDesc('starts_on')->get();

        $requestedId = $request->integer('season');
        $selected = $requestedId !== 0
            ? $seasons->firstWhere('id', $requestedId)
            : $seasons->first();

        $skillLevel = SkillLevel::tryFrom($request->string('skill_level')->toString());

        return Inertia::render('client/leaderboard', [
            'seasons' => SeasonResource::collection($seasons),
            'standings' => $selected !== null
                ? $this->standings->forSeason($selected, $skillLevel)->all()
                : [],
            'skillLevels' => SkillLevel::options(),
            'filters' => [
                'season' => $selected?->id,
                'skill_level' => $skillLevel?->value,
            ],
        ]);
    }
}
