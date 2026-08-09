<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SkillLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeasonRequest;
use App\Http\Requests\Admin\UpdateSeasonRequest;
use App\Http\Resources\SeasonResource;
use App\Models\Season;
use App\Services\AuditLogger;
use App\Services\SeasonStandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeasonController extends Controller
{
    public function __construct(
        private SeasonStandingsService $standings,
        private AuditLogger $audit,
    ) {}

    /**
     * List seasons, newest window first.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Season::class);

        $seasons = Season::query()->orderByDesc('starts_on')->get();

        return Inertia::render('admin/seasons/index', [
            'seasons' => SeasonResource::collection($seasons),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Season::class);

        return Inertia::render('admin/seasons/create');
    }

    public function store(StoreSeasonRequest $request): RedirectResponse
    {
        $season = Season::create($request->validated());

        $this->audit->log('season.created', $season, ['name' => $season->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Season created.')]);

        return to_route('admin.seasons.index');
    }

    public function edit(Season $season): Response
    {
        $this->authorize('update', $season);

        return Inertia::render('admin/seasons/edit', [
            'season' => new SeasonResource($season),
        ]);
    }

    public function update(UpdateSeasonRequest $request, Season $season): RedirectResponse
    {
        $season->update($request->validated());

        $this->audit->log('season.updated', $season, ['name' => $season->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Season updated.')]);

        return to_route('admin.seasons.index');
    }

    /**
     * Season leaderboard with an optional skill-tier filter.
     */
    public function leaderboard(Request $request, Season $season): Response
    {
        $this->authorize('update', $season);

        $skillLevel = SkillLevel::tryFrom($request->string('skill_level')->toString());

        return Inertia::render('admin/seasons/leaderboard', [
            'season' => new SeasonResource($season),
            'standings' => $this->standings->forSeason($season, $skillLevel)->all(),
            'skillLevels' => SkillLevel::options(),
            'filters' => ['skill_level' => $skillLevel?->value],
        ]);
    }
}
