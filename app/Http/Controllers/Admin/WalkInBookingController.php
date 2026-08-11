<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWalkInBookingRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BookingSelectionService;
use App\Services\WalkInBookingService;
use App\Settings\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class WalkInBookingController extends Controller
{
    public function __construct(
        private WalkInBookingService $walkIns,
        private AuditLogger $audit,
    ) {}

    /**
     * Show the walk-in booking form. Availability and customer-search results
     * are recomputed on Inertia partial reloads driven by query parameters, so
     * no bespoke JSON endpoints are required.
     */
    public function create(Request $request, AppSettings $settings, BookingSelectionService $selection): Response
    {
        $this->authorize('create', Booking::class);

        $today = Carbon::today();

        $courts = Court::query()->active()->orderBy('name')
            ->get(['id', 'name', 'hourly_rate']);

        $availability = null;
        $selectedCourt = $request->integer('court_id') ?: null;

        if ($selectedCourt !== null) {
            // Load the full model: availability reads is_active and the court's
            // base operating hours, which the slim list above does not select.
            $court = Court::query()->active()->find($selectedCourt);

            if ($court !== null) {
                $date = $request->filled('date')
                    ? Carbon::parse($request->string('date')->toString())
                    : $today->copy();

                $availability = $selection->forCourtAndDate($court, $date);
            }
        }

        return Inertia::render('admin/bookings/walk-in', [
            'courts' => $courts,
            'equipment' => Equipment::query()->active()->orderBy('name')
                ->get(['id', 'name', 'hourly_rate']),
            'availability' => $availability,
            'customerResults' => $this->searchCustomers($request->string('customer_q')->toString()),
            'paymentMethods' => PaymentMethod::options(),
            'constraints' => [
                'min_date' => $today->toDateString(),
                'max_date' => $today->copy()->addDays($settings->advanceBookingDays())->toDateString(),
                'max_duration' => $settings->maxBookingHours(),
            ],
        ]);
    }

    /**
     * Create the walk-in booking. Cash bookings are confirmed on the spot and
     * land on the booking page; GCash bookings redirect to the provider's
     * checkout so the customer can pay before the hold lapses.
     */
    public function store(StoreWalkInBookingRequest $request, AppSettings $settings): RedirectResponse|Response
    {
        $data = $request->validated();

        $result = $this->walkIns->create([
            'customer' => [
                'mode' => $data['customer']['mode'],
                'user_id' => $data['customer']['user_id'] ?? null,
                'name' => $data['customer']['name'] ?? null,
                'phone' => $data['customer']['phone'] ?? null,
                'email' => $data['customer']['email'] ?? null,
            ],
            'court_id' => (int) $data['court_id'],
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'duration' => (int) $data['duration'],
            'equipment' => $data['equipment'] ?? [],
            'payment_method' => $data['payment_method'],
        ]);

        $booking = $result['booking'];

        $this->audit->log('booking.walk_in_created', $booking, [
            'reference' => $booking->booking_reference,
            'payment_method' => $data['payment_method'],
            'guest' => $booking->user->is_guest,
        ]);

        // GCash: send the customer straight to the provider checkout.
        if ($result['checkout_url'] !== null) {
            return Inertia::location($result['checkout_url']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Walk-in booking created.')]);

        return to_route('admin.bookings.show', $booking);
    }

    /**
     * Search existing (non-guest) customers by name, email or phone.
     *
     * @return array<int, array{id: int, name: string, email: string|null, phone: string|null}>
     */
    private function searchCustomers(string $query): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        return User::query()->customers()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ])
            ->all();
    }
}
