<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpenPlayGameSubmissionRequest;
use App\Models\OpenPlaySession;
use App\Services\OpenPlayGameSubmissionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OpenPlayGameSubmissionController extends Controller
{
    public function __construct(private OpenPlayGameSubmissionService $submissions) {}

    /**
     * Submit a player-reported game result for organizer review.
     */
    public function store(StoreOpenPlayGameSubmissionRequest $request, OpenPlaySession $openPlaySession): RedirectResponse
    {
        $validated = $request->validated();

        $this->submissions->submit(
            $openPlaySession,
            $request->user(),
            $validated['team_a'],
            $validated['team_b'],
            (int) $validated['team_a_score'],
            (int) $validated['team_b_score'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Result submitted for review.')]);

        return to_route('open-play.show', $openPlaySession);
    }
}
