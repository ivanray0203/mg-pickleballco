<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpenPlayMatchup;
use App\Models\OpenPlaySession;
use App\Services\AuditLogger;
use App\Services\OpenPlayMatchupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OpenPlayMatchupController extends Controller
{
    public function __construct(
        private OpenPlayMatchupService $matchups,
        private AuditLogger $audit,
    ) {}

    /**
     * Shuffle the checked-in roster into 2v2 matchups and queue them.
     */
    public function generate(OpenPlaySession $openPlaySession): RedirectResponse
    {
        $this->authorize('update', $openPlaySession);

        $created = $this->matchups->generate($openPlaySession);

        $this->audit->log('open_play.matchups_generated', $openPlaySession, ['count' => $created]);

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count match queued|:count matches queued', $created, ['count' => $created])]);

        return back();
    }

    /**
     * Record the score for the currently playing matchup.
     */
    public function record(Request $request, OpenPlayMatchup $matchup): RedirectResponse
    {
        $this->authorize('update', $matchup->session);

        $validated = $request->validate([
            'team_a_score' => ['required', 'integer', 'min:0', 'max:99'],
            'team_b_score' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $this->matchups->recordResult($matchup, (int) $validated['team_a_score'], (int) $validated['team_b_score']);

        return back();
    }

    /**
     * Move a playing matchup to a different court.
     */
    public function assignCourt(Request $request, OpenPlayMatchup $matchup): RedirectResponse
    {
        $this->authorize('update', $matchup->session);

        $validated = $request->validate([
            'court_label' => ['required', 'string', 'max:50'],
        ]);

        $this->matchups->assignCourt($matchup, $validated['court_label']);

        return back();
    }

    /**
     * Remove a queued (or playing) matchup from the queue.
     */
    public function destroy(OpenPlayMatchup $matchup): RedirectResponse
    {
        $this->authorize('update', $matchup->session);

        $this->matchups->cancel($matchup);

        return back();
    }

    /**
     * Clear the entire un-played queue for a session.
     */
    public function clear(OpenPlaySession $openPlaySession): RedirectResponse
    {
        $this->authorize('update', $openPlaySession);

        $this->matchups->clearQueue($openPlaySession);

        return back();
    }
}
