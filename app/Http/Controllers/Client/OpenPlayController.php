<?php

namespace App\Http\Controllers\Client;

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Http\Controllers\Controller;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Notifications\OpenPlayRegistrationConfirmed;
use App\Services\OpenPlayService;
use App\Services\OpenPlayStandingsService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OpenPlayController extends Controller
{
    public function __construct(
        private OpenPlayService $openPlay,
        private OpenPlayStandingsService $standings,
        private PaymentService $payments,
    ) {}

    /**
     * List upcoming sessions that are open for registration.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'skill_level' => $request->string('skill_level')->toString() ?: null,
            'date' => $request->string('date')->toString() ?: null,
        ];

        $sessions = OpenPlaySession::query()
            ->with('court')
            ->upcoming()
            ->whereIn('status', [OpenPlaySessionStatus::Scheduled, OpenPlaySessionStatus::Open])
            ->when($filters['skill_level'], fn ($query, $level) => $query->where('skill_level', $level))
            ->when($filters['date'], fn ($query, $date) => $query->whereDate('session_date', $date))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->paginate(9)
            ->withQueryString();

        $myRegistrations = $this->myRegistrationsFor($request, $sessions->getCollection()->pluck('id')->all());

        return Inertia::render('client/open-play', [
            'sessions' => [
                'data' => $sessions->getCollection()
                    ->map(fn (OpenPlaySession $session): array => $this->serialize($session, $myRegistrations[$session->id] ?? null))
                    ->all(),
                'meta' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'from' => $sessions->firstItem(),
                    'to' => $sessions->lastItem(),
                    'total' => $sessions->total(),
                    'links' => $sessions->linkCollection()->toArray(),
                    'path' => $sessions->path(),
                    'per_page' => $sessions->perPage(),
                ],
            ],
            'filters' => $filters,
            'skillLevels' => SkillLevel::options(),
        ]);
    }

    /**
     * Show a single session with the customer's own registration state.
     */
    public function show(Request $request, OpenPlaySession $openPlaySession): Response
    {
        $openPlaySession->load('court');

        $registration = $this->myRegistration($request, $openPlaySession);

        $showsStandings = in_array($openPlaySession->status, [
            OpenPlaySessionStatus::InProgress,
            OpenPlaySessionStatus::Completed,
        ], true);

        // Self-reporting is open while the session is in progress, to the
        // viewer if they are a confirmed/checked-in participant.
        $canReport = $openPlaySession->status === OpenPlaySessionStatus::InProgress
            && $registration !== null
            && in_array($registration->status, [RegistrationStatus::Confirmed, RegistrationStatus::CheckedIn], true);

        $participants = $canReport
            ? $openPlaySession->registrations()
                ->with('user')
                ->whereIn('status', [RegistrationStatus::Confirmed, RegistrationStatus::CheckedIn])
                ->get()
                ->map(fn (OpenPlayRegistration $r): array => ['registration_id' => $r->id, 'name' => $r->user->name])
                ->values()
                ->all()
            : [];

        return Inertia::render('client/open-play-detail', [
            'session' => $this->serialize($openPlaySession, $registration),
            'standings' => $showsStandings
                ? $this->standings->forSession($openPlaySession)->all()
                : [],
            'standingsState' => $showsStandings
                ? ($openPlaySession->status === OpenPlaySessionStatus::Completed ? 'final' : 'live')
                : null,
            'canReport' => $canReport,
            'participants' => $participants,
            'myRegistrationId' => $canReport ? $registration->id : null,
        ]);
    }

    /**
     * Register the customer. Free sessions confirm immediately; paid sessions
     * reserve a hold and redirect to GCash checkout.
     */
    public function register(Request $request, OpenPlaySession $openPlaySession): SymfonyResponse
    {
        $registration = $this->openPlay->register($openPlaySession, $request->user());

        if ($registration->status === RegistrationStatus::Reserved) {
            $payment = $this->payments->startCheckoutForRegistration($registration);

            return Inertia::location($payment->checkout_url);
        }

        // Free session — confirmed immediately, so notify now (paid sessions
        // notify from the payment webhook instead).
        $request->user()->notify(new OpenPlayRegistrationConfirmed($registration));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You are registered for this session.')]);

        return to_route('open-play.show', $openPlaySession);
    }

    /**
     * Resume checkout for an existing reserved (unpaid) registration.
     */
    public function pay(Request $request, OpenPlaySession $openPlaySession): SymfonyResponse
    {
        $registration = $this->myRegistration($request, $openPlaySession);

        if ($registration === null || $registration->status !== RegistrationStatus::Reserved || $registration->isExpired()) {
            return to_route('open-play.show', $openPlaySession)
                ->with('error', 'This registration can no longer be paid.');
        }

        $payment = $this->payments->startCheckoutForRegistration($registration);

        return Inertia::location($payment->checkout_url);
    }

    /**
     * Cancel the customer's own registration (owner only).
     */
    public function cancel(Request $request, OpenPlayRegistration $openPlayRegistration): RedirectResponse
    {
        abort_unless($openPlayRegistration->user_id === $request->user()->id, 403);

        $this->openPlay->cancelRegistration($openPlayRegistration);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Your registration was cancelled.')]);

        return to_route('open-play.show', $openPlayRegistration->open_play_session_id);
    }

    /**
     * The customer's most recent registration for a session (any status).
     */
    private function myRegistration(Request $request, OpenPlaySession $session): ?OpenPlayRegistration
    {
        return OpenPlayRegistration::query()
            ->where('open_play_session_id', $session->id)
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->first();
    }

    /**
     * Map session id => the customer's registration, for a set of sessions.
     *
     * @param  array<int, int>  $sessionIds
     * @return array<int, OpenPlayRegistration>
     */
    private function myRegistrationsFor(Request $request, array $sessionIds): array
    {
        if ($sessionIds === []) {
            return [];
        }

        return OpenPlayRegistration::query()
            ->whereIn('open_play_session_id', $sessionIds)
            ->where('user_id', $request->user()->id)
            ->get()
            ->keyBy('open_play_session_id')
            ->all();
    }

    /**
     * Customer-facing serialization of a session plus the viewer's own state.
     *
     * @return array<string, mixed>
     */
    private function serialize(OpenPlaySession $session, ?OpenPlayRegistration $registration): array
    {
        $price = $session->price_amount / 100;

        return [
            'id' => $session->id,
            'title' => $session->title,
            'court' => $session->court->name,
            'session_date' => $session->session_date->toDateString(),
            'session_date_label' => $session->session_date->format('l, F j, Y'),
            'start_time' => substr((string) $session->start_time, 0, 5),
            'end_time' => substr((string) $session->end_time, 0, 5),
            'skill_level_label' => $session->skill_level?->label(),
            'price_formatted' => $session->isFree() ? 'Free' : '₱'.number_format($price, 2),
            'is_free' => $session->isFree(),
            'capacity' => $session->capacity,
            'remaining_slots' => $session->remainingSlots(),
            'is_full' => $session->isFull(),
            'notes' => $session->notes,
            'my_registration' => $registration === null ? null : [
                'id' => $registration->id,
                'status' => $registration->status->value,
                'status_label' => $registration->status->label(),
                'is_active' => $registration->status->occupiesSlot(),
                'awaiting_payment' => $registration->status === RegistrationStatus::Reserved && ! $registration->isExpired(),
            ],
        ];
    }
}
