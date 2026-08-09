<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOpenPlayGameRequest;
use App\Models\OpenPlayGame;
use App\Models\OpenPlaySession;
use App\Services\AuditLogger;
use App\Services\OpenPlayGameService;
use Illuminate\Http\RedirectResponse;

class OpenPlayGameController extends Controller
{
    public function __construct(
        private OpenPlayGameService $games,
        private AuditLogger $audit,
    ) {}

    /**
     * Record a game for a session.
     */
    public function store(StoreOpenPlayGameRequest $request, OpenPlaySession $openPlaySession): RedirectResponse
    {
        $validated = $request->validated();

        $game = $this->games->recordGame(
            $openPlaySession,
            $validated['team_a'],
            $validated['team_b'],
            (int) $validated['team_a_score'],
            (int) $validated['team_b_score'],
        );

        $this->audit->log('open_play.game_recorded', $game, [
            'session_id' => $openPlaySession->id,
            'score' => $game->team_a_score.'-'.$game->team_b_score,
        ]);

        return back();
    }

    /**
     * Delete a recorded game so an organizer can correct a mistake.
     */
    public function destroy(OpenPlayGame $game): RedirectResponse
    {
        $this->authorize('update', $game->session);

        $sessionId = $game->open_play_session_id;

        $this->games->deleteGame($game);

        $this->audit->log('open_play.game_deleted', null, [
            'session_id' => $sessionId,
            'game_id' => $game->id,
        ]);

        return back();
    }
}
