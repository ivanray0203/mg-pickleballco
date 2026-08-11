<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchupStatus;
use App\Enums\OpenPlayGameSubmissionStatus;
use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOpenPlaySessionRequest;
use App\Http\Requests\Admin\UpdateOpenPlaySessionRequest;
use App\Http\Resources\OpenPlayGameResource;
use App\Http\Resources\OpenPlayRegistrationResource;
use App\Http\Resources\OpenPlaySessionResource;
use App\Models\Court;
use App\Models\OpenPlayGameSubmission;
use App\Models\OpenPlayMatchup;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Services\AuditLogger;
use App\Services\OpenPlayGameService;
use App\Services\OpenPlayService;
use App\Services\OpenPlayStandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OpenPlayController extends Controller
{
    public function __construct(
        private OpenPlayService $sessions,
        private OpenPlayGameService $games,
        private OpenPlayStandingsService $standings,
        private AuditLogger $audit,
    ) {}

    /**
     * Display a paginated, filterable list of open-play sessions.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OpenPlaySession::class);

        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'court' => $request->string('court')->toString() ?: null,
        ];

        $sessions = OpenPlaySession::query()
            ->with('court')
            ->when($filters['search'], fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['court'], fn ($query, $court) => $query->where('court_id', $court))
            ->orderByDesc('session_date')
            ->orderBy('start_time')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/open-play/index', [
            'sessions' => OpenPlaySessionResource::collection($sessions),
            'filters' => $filters,
            'statuses' => OpenPlaySessionStatus::options(),
            'courts' => $this->courtOptions(),
        ]);
    }

    /**
     * Show the form for creating a new session.
     */
    public function create(): Response
    {
        $this->authorize('create', OpenPlaySession::class);

        return Inertia::render('admin/open-play/create', [
            'courts' => $this->courtOptions(activeOnly: true),
            'skillLevels' => SkillLevel::options(),
        ]);
    }

    /**
     * Persist a newly created session.
     */
    public function store(StoreOpenPlaySessionRequest $request): RedirectResponse
    {
        $session = $this->sessions->create($request->validated());

        $this->audit->log('open_play.created', $session, ['title' => $session->title]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Open play session created.')]);

        return to_route('admin.open-play.index');
    }

    /**
     * Show the form for editing an existing session.
     */
    public function edit(OpenPlaySession $openPlaySession): Response
    {
        $this->authorize('update', $openPlaySession);

        return Inertia::render('admin/open-play/edit', [
            'session' => new OpenPlaySessionResource($openPlaySession->load('court')),
            'courts' => $this->courtOptions(activeOnly: true),
            'skillLevels' => SkillLevel::options(),
        ]);
    }

    /**
     * Update an existing session.
     */
    public function update(UpdateOpenPlaySessionRequest $request, OpenPlaySession $openPlaySession): RedirectResponse
    {
        $session = $this->sessions->update($openPlaySession, $request->validated());

        $this->audit->log('open_play.updated', $session, ['title' => $session->title]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Open play session updated.')]);

        return to_route('admin.open-play.index');
    }

    /**
     * Cancel a session, freeing its court window.
     */
    public function cancel(OpenPlaySession $openPlaySession): RedirectResponse
    {
        $this->authorize('update', $openPlaySession);

        $session = $this->sessions->cancel($openPlaySession);

        $this->audit->log('open_play.cancelled', $session, ['title' => $session->title]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Open play session cancelled.')]);

        return back();
    }

    /**
     * The session-day console: roster with check-in, recorded games, and the
     * running standings preview.
     */
    public function manage(OpenPlaySession $openPlaySession): Response
    {
        $this->authorize('update', $openPlaySession);

        $openPlaySession->load('court');

        $registrations = $openPlaySession->registrations()
            ->with('user')
            ->whereIn('status', RegistrationStatus::occupyingValues())
            ->get()
            ->sortBy('user.name')
            ->values();

        // The game log is the only list here that grows without bound over a
        // session, so it pages under its own key; the roster, queue and pending
        // submissions stay whole because the recording form reads them all.
        $games = $openPlaySession->games()
            ->with('players.registration.user')
            ->orderByDesc('played_at')
            ->orderByDesc('id')
            ->paginate(15, pageName: 'games_page')
            ->withQueryString();

        // Map every registration in the session to a player name so reported
        // submissions (which store registration ids) can be shown by name.
        $names = $openPlaySession->registrations()
            ->with('user')
            ->get()
            ->mapWithKeys(fn (OpenPlayRegistration $registration): array => [
                $registration->id => $registration->user->name,
            ]);

        $submissions = OpenPlayGameSubmission::query()
            ->where('open_play_session_id', $openPlaySession->id)
            ->where('status', OpenPlayGameSubmissionStatus::Pending)
            ->with('reporter')
            ->orderByDesc('id')
            ->get()
            ->map(fn (OpenPlayGameSubmission $submission): array => [
                'id' => $submission->id,
                'reporter_name' => $submission->reporter->name,
                'team_a' => array_map(fn (int $id): string => $names[$id] ?? 'Unknown', $submission->team_a),
                'team_b' => array_map(fn (int $id): string => $names[$id] ?? 'Unknown', $submission->team_b),
                'team_a_score' => $submission->team_a_score,
                'team_b_score' => $submission->team_b_score,
            ])
            ->all();

        $matchups = OpenPlayMatchup::query()
            ->where('open_play_session_id', $openPlaySession->id)
            ->whereIn('status', [MatchupStatus::Queued, MatchupStatus::Playing])
            ->orderBy('position')
            ->get()
            ->map(fn (OpenPlayMatchup $matchup): array => [
                'id' => $matchup->id,
                'status' => $matchup->status->value,
                'status_label' => $matchup->status->label(),
                'court_label' => $matchup->court_label,
                'team_a' => array_map(fn (int $id): string => $names[$id] ?? 'Unknown', $matchup->team_a),
                'team_b' => array_map(fn (int $id): string => $names[$id] ?? 'Unknown', $matchup->team_b),
            ])
            ->all();

        $courts = array_map(
            fn (int $number): string => "Court {$number}",
            range(1, max(1, $openPlaySession->court_count)),
        );

        return Inertia::render('admin/open-play/manage', [
            'session' => new OpenPlaySessionResource($openPlaySession),
            'registrations' => OpenPlayRegistrationResource::collection($registrations),
            'games' => OpenPlayGameResource::collection($games),
            'standings' => $this->standings->forSession($openPlaySession)->all(),
            'submissions' => $submissions,
            'matchups' => $matchups,
            'courts' => $courts,
        ]);
    }

    /**
     * Check a player in for the session.
     */
    public function checkIn(OpenPlayRegistration $registration): RedirectResponse
    {
        $this->authorize('update', $registration->session);

        $this->games->checkIn($registration);

        return back();
    }

    /**
     * Undo a player's check-in.
     */
    public function undoCheckIn(OpenPlayRegistration $registration): RedirectResponse
    {
        $this->authorize('update', $registration->session);

        $this->games->undoCheckIn($registration);

        return back();
    }

    /**
     * Close the session for game recording.
     */
    public function complete(OpenPlaySession $openPlaySession): RedirectResponse
    {
        $this->authorize('update', $openPlaySession);

        $session = $this->games->complete($openPlaySession);

        $this->audit->log('open_play.completed', $session, ['title' => $session->title]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Open play session completed.')]);

        return back();
    }

    /**
     * Court options for select inputs and filters. Session forms should only
     * offer active courts; the list filter shows every court.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function courtOptions(bool $activeOnly = false): array
    {
        return Court::query()
            ->when($activeOnly, fn ($query) => $query->active())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Court $court): array => ['value' => (string) $court->id, 'label' => $court->name])
            ->all();
    }
}
