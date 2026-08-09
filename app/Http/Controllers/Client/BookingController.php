<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\CourtResource;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Services\BookingSelectionService;
use App\Services\BookingService;
use App\Services\EquipmentAvailabilityService;
use App\Settings\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private BookingSelectionService $selection,
        private BookingService $bookings,
        private EquipmentAvailabilityService $equipment,
    ) {}

    /**
     * List the signed-in customer's own bookings, filtered by lifecycle.
     */
    public function index(Request $request): Response
    {
        $filter = $request->string('filter', 'upcoming')->toString();

        $bookings = $request->user()->bookings()
            ->with(['court', 'latestPayment'])
            ->tap(fn ($query) => $this->applyFilter($query, $filter))
            ->orderBy('booking_date', $filter === 'upcoming' ? 'asc' : 'desc')
            ->orderBy('start_time')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('client/bookings', [
            'bookings' => BookingResource::collection($bookings),
            'filter' => in_array($filter, ['upcoming', 'completed', 'cancelled', 'expired'], true)
                ? $filter
                : 'upcoming',
        ]);
    }

    /**
     * Constrain the booking query to the selected lifecycle filter.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\HasMany<Booking, \App\Models\User>  $query
     */
    private function applyFilter($query, string $filter): void
    {
        match ($filter) {
            'completed' => $query->where('status', BookingStatus::Completed),
            'cancelled' => $query->where('status', BookingStatus::Cancelled),
            'expired' => $query->where('status', BookingStatus::Expired),
            default => $query->upcoming(),
        };
    }

    /**
     * Show the date/time/duration selection screen for a preselected court.
     *
     * Date changes are made with an Inertia partial reload of the `availability`
     * prop, so no bespoke JSON endpoint is needed and the shared availability
     * domain logic is reused unchanged.
     */
    public function create(Request $request, AppSettings $settings): Response|RedirectResponse
    {
        $validated = $request->validate([
            'court' => ['required', 'string'],
            'date' => ['nullable', 'date'],
        ]);

        $court = $this->activeCourtOrNull($validated['court']);

        if ($court === null) {
            return to_route('courts.index');
        }

        $today = Carbon::today();
        $date = isset($validated['date'])
            ? Carbon::parse($validated['date'])->startOfDay()
            : $today->copy();

        return Inertia::render('client/book', [
            'court' => new CourtResource($court),
            'date' => $date->toDateString(),
            'availability' => $this->selection->forCourtAndDate($court, $date),
            'constraints' => [
                'min_date' => $today->toDateString(),
                'max_date' => $today->copy()->addDays($settings->advanceBookingDays())->toDateString(),
                'max_duration' => $settings->maxBookingHours(),
            ],
        ]);
    }

    /**
     * Review the selection with server-derived pricing before payment. All
     * figures are recomputed from authoritative data; nothing is trusted from
     * the client. An invalid or unavailable selection returns to selection.
     */
    public function review(Request $request): Response|RedirectResponse
    {
        $validated = $request->validate([
            'court' => ['required', 'string'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'regex:/^([01]\d|2[0-3]):00$/'],
            'duration' => ['required', 'integer', 'min:1', 'max:24'],
        ]);

        $court = $this->activeCourtOrNull($validated['court']);

        if ($court === null) {
            return to_route('courts.index');
        }

        $date = Carbon::parse($validated['date'])->startOfDay();
        $end = $this->bookings->endTime($validated['start_time'], (int) $validated['duration']);

        try {
            $quote = $this->bookings->previewQuote($court, $date, $validated['start_time'], $end);
        } catch (ValidationException) {
            return to_route('book', ['court' => $court->slug, 'date' => $date->toDateString()])
                ->with('error', 'That selection is no longer available. Please choose another time.');
        }

        return Inertia::render('client/booking-review', [
            'court' => new CourtResource($court),
            'summary' => [
                'date' => $date->toDateString(),
                'start_time' => $quote['start_time'],
                'end_time' => $quote['end_time'],
                'duration_hours' => $quote['duration_hours'],
                'hourly_rate' => round($quote['hourly_rate'] / 100, 2),
                'hourly_rate_formatted' => '₱'.number_format($quote['hourly_rate'] / 100, 2),
                'total_amount' => round($quote['total_amount'] / 100, 2),
                'total_amount_formatted' => '₱'.number_format($quote['total_amount'] / 100, 2),
            ],
            // Equipment available for this exact slot; the client prices a
            // live display total, the server recomputes authoritatively.
            'equipment' => $this->equipment->catalogueFor(
                $date, $quote['start_time'], $quote['end_time'],
            ),
        ]);
    }

    /**
     * Create a concurrency-safe pending-payment hold for the selection.
     */
    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $court = $this->activeCourtOrNull($validated['court']);

        if ($court === null) {
            return to_route('courts.index');
        }

        $end = $this->bookings->endTime($validated['start_time'], (int) $validated['duration']);

        $booking = $this->bookings->create([
            'user_id' => $request->user()->id,
            'court_id' => $court->id,
            'booking_date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $end,
            'equipment' => $validated['equipment'] ?? [],
        ]);

        return to_route('bookings.show', $booking);
    }

    /**
     * Show a booking hold with its authoritative expiry, for the owner only.
     */
    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['court', 'user', 'latestPayment', 'equipmentItems']);

        return Inertia::render('client/booking-detail', [
            'booking' => new BookingResource($booking),
            'expired' => $booking->isExpired(),
            'server_now' => Carbon::now()->toIso8601String(),
        ]);
    }

    private function activeCourtOrNull(string $slug): ?Court
    {
        return Court::query()->active()->where('slug', $slug)->first();
    }
}
