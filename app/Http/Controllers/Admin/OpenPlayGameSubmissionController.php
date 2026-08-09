<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpenPlayGameSubmission;
use App\Services\AuditLogger;
use App\Services\OpenPlayGameSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OpenPlayGameSubmissionController extends Controller
{
    public function __construct(
        private OpenPlayGameSubmissionService $submissions,
        private AuditLogger $audit,
    ) {}

    /**
     * Confirm a player-reported result, promoting it to a recorded game.
     */
    public function confirm(Request $request, OpenPlayGameSubmission $submission): RedirectResponse
    {
        $this->authorize('update', $submission->session);

        $game = $this->submissions->confirm($submission, $request->user());

        $this->audit->log('open_play.submission_confirmed', $game, [
            'session_id' => $submission->open_play_session_id,
            'submission_id' => $submission->id,
        ]);

        return back();
    }

    /**
     * Reject a player-reported result.
     */
    public function reject(Request $request, OpenPlayGameSubmission $submission): RedirectResponse
    {
        $this->authorize('update', $submission->session);

        $this->submissions->reject($submission, $request->user());

        $this->audit->log('open_play.submission_rejected', null, [
            'session_id' => $submission->open_play_session_id,
            'submission_id' => $submission->id,
        ]);

        return back();
    }
}
