<?php

namespace App\Services;

use App\Enums\OpenPlaySessionStatus;
use App\Enums\RegistrationStatus;
use App\Models\Court;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlaySession;
use App\Models\User;
use App\Notifications\OpenPlaySessionCancelled;
use App\Settings\AppSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns the open-play session lifecycle. A session occupies a court for a whole
 * window, so creation/updates run a concurrency-safe collision check against
 * both private bookings and other sessions on the same court and date.
 */
class OpenPlayService
{
    public function __construct(
        private CourtAvailabilityService $availability,
        private AppSettings $settings,
    ) {}

    /**
     * Register a customer for a session. Concurrency-safe: occupying rows are
     * locked and counted before insert so capacity is never exceeded. Free
     * sessions confirm immediately; paid sessions are reserved as a hold that
     * expires unless payment completes. A previously cancelled registration is
     * reactivated in place (respecting the one-per-user constraint).
     */
    public function register(OpenPlaySession $session, User $user): OpenPlayRegistration
    {
        if (! $session->status->acceptsRegistrations()) {
            throw ValidationException::withMessages([
                'session' => 'This session is not open for registration.',
            ]);
        }

        if ($session->session_date->lt(Carbon::today())) {
            throw ValidationException::withMessages([
                'session' => 'This session has already passed.',
            ]);
        }

        return DB::transaction(function () use ($session, $user) {
            $existing = OpenPlayRegistration::query()
                ->where('open_play_session_id', $session->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && $existing->status->occupiesSlot()) {
                throw ValidationException::withMessages([
                    'session' => 'You are already registered for this session.',
                ]);
            }

            $occupied = OpenPlayRegistration::query()
                ->where('open_play_session_id', $session->id)
                ->whereIn('status', RegistrationStatus::occupyingValues())
                ->lockForUpdate()
                ->count();

            if ($occupied >= $session->capacity) {
                throw ValidationException::withMessages([
                    'session' => 'This session is full.',
                ]);
            }

            $isFree = $session->isFree();

            $attributes = [
                'status' => $isFree ? RegistrationStatus::Confirmed : RegistrationStatus::Reserved,
                'payment_id' => null,
                'cancelled_at' => null,
                'expires_at' => $isFree
                    ? null
                    : Carbon::now()->addMinutes($this->settings->bookingHoldMinutes()),
            ];

            if ($existing !== null) {
                $existing->update($attributes);

                return $existing;
            }

            return OpenPlayRegistration::create([
                'open_play_session_id' => $session->id,
                'user_id' => $user->id,
                ...$attributes,
            ]);
        });
    }

    /**
     * Cancel a registration, freeing its slot. Idempotent.
     */
    public function cancelRegistration(OpenPlayRegistration $registration): OpenPlayRegistration
    {
        if ($registration->status !== RegistrationStatus::Cancelled) {
            $registration->update([
                'status' => RegistrationStatus::Cancelled,
                'cancelled_at' => Carbon::now(),
                'expires_at' => null,
            ]);
        }

        return $registration;
    }

    /**
     * Create a new open-play session from validated data.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OpenPlaySession
    {
        return $this->persist($data, null);
    }

    /**
     * Update an existing session, re-checking for collisions (excluding itself).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(OpenPlaySession $session, array $data): OpenPlaySession
    {
        return $this->persist($data, $session);
    }

    /**
     * Cancel a session, freeing its court window.
     */
    public function cancel(OpenPlaySession $session): OpenPlaySession
    {
        if ($session->status === OpenPlaySessionStatus::Cancelled) {
            throw ValidationException::withMessages([
                'status' => 'This session is already cancelled.',
            ]);
        }

        $session->update(['status' => OpenPlaySessionStatus::Cancelled]);

        // Notify everyone still holding a slot (refunds are a policy hook only).
        $session->registrations()
            ->whereIn('status', RegistrationStatus::occupyingValues())
            ->with('user')
            ->get()
            ->each(fn (OpenPlayRegistration $registration) => $registration->user->notify(new OpenPlaySessionCancelled($session)));

        return $session;
    }

    /**
     * Shared create/update path: validate the window and persist under a lock.
     *
     * @param  array<string, mixed>  $data
     */
    private function persist(array $data, ?OpenPlaySession $existing): OpenPlaySession
    {
        $court = Court::query()->whereKey($data['court_id'])->firstOrFail();
        $date = Carbon::parse($data['session_date'])->startOfDay();
        $start = $data['start_time'];
        $end = $data['end_time'];

        $this->assertCourtIsOpen($court, $date, $start, $end);

        return DB::transaction(function () use ($court, $date, $start, $end, $data, $existing) {
            $this->assertNoConflict($court, $date, $start, $end, $existing?->id);

            $attributes = [
                'court_id' => $court->id,
                'title' => $data['title'],
                'session_date' => $date->toDateString(),
                'start_time' => $start,
                'end_time' => $end,
                'capacity' => (int) $data['capacity'],
                'court_count' => (int) ($data['court_count'] ?? 1),
                'skill_level' => $data['skill_level'] ?? null,
                'price_amount' => (int) round(((float) $data['price']) * 100),
                'notes' => $data['notes'] ?? null,
            ];

            if ($existing) {
                $existing->update($attributes);

                return $existing;
            }

            return OpenPlaySession::create([
                ...$attributes,
                'status' => OpenPlaySessionStatus::Open,
            ]);
        });
    }

    /**
     * Ensure the court is active, open on that day, and that the window sits
     * inside its operating hours. `isWithinOperatingHours()` only covers the
     * day schedule, so the active check is enforced here.
     */
    private function assertCourtIsOpen(Court $court, Carbon $date, string $start, string $end): void
    {
        if (! $court->is_active) {
            throw ValidationException::withMessages([
                'court_id' => 'This court is inactive and cannot host open play.',
            ]);
        }

        if (! $this->availability->isWithinOperatingHours($court, $date, $start, $end)) {
            throw ValidationException::withMessages([
                'start_time' => 'The session must fall within the court\'s operating hours for that day.',
            ]);
        }
    }

    /**
     * Reject a session that overlaps an existing booking or another active
     * session on the same court and date. Rows are locked so two concurrent
     * writers cannot both pass.
     */
    private function assertNoConflict(Court $court, Carbon $date, string $start, string $end, ?int $ignoreId): void
    {
        $bookingConflict = $court->bookings()
            ->whereDate('booking_date', $date->toDateString())
            ->reservesSlot()
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->lockForUpdate()
            ->exists();

        if ($bookingConflict) {
            throw ValidationException::withMessages([
                'start_time' => 'This time range overlaps an existing booking.',
            ]);
        }

        $sessionConflict = OpenPlaySession::query()
            ->where('court_id', $court->id)
            ->whereDate('session_date', $date->toDateString())
            ->whereIn('status', OpenPlaySessionStatus::blockingValues())
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->lockForUpdate()
            ->exists();

        if ($sessionConflict) {
            throw ValidationException::withMessages([
                'start_time' => 'This time range overlaps another open-play session.',
            ]);
        }
    }
}
