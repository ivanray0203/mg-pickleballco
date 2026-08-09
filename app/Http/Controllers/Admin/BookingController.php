<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Court;
use App\Services\AuditLogger;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookings,
        private AuditLogger $audit,
    ) {}

    /**
     * Display a paginated, filterable list of bookings.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Booking::class);

        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'date' => $request->string('date')->toString() ?: null,
            'court' => $request->string('court')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];

        $bookings = Booking::query()
            ->with(['user', 'court'])
            ->when($filters['search'], fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('booking_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->when($filters['date'], fn ($query, $date) => $query->whereDate('booking_date', $date))
            ->when($filters['court'], fn ($query, $court) => $query->where('court_id', $court))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->latest('booking_date')
            ->latest('start_time')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/bookings/index', [
            'bookings' => BookingResource::collection($bookings),
            'filters' => $filters,
            'courts' => Court::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => BookingStatus::options(),
        ]);
    }

    /**
     * Show a single booking's details.
     */
    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        $booking->load(['user', 'court']);

        return Inertia::render('admin/bookings/show', [
            'booking' => new BookingResource($booking),
        ]);
    }

    /**
     * Cancel an eligible booking.
     */
    public function cancel(CancelBookingRequest $request, Booking $booking): RedirectResponse
    {
        $reason = $request->string('cancellation_reason')->toString();

        $this->bookings->cancel($booking, $reason);

        $this->audit->log('booking.cancelled', $booking, [
            'reference' => $booking->booking_reference,
            'reason' => $reason,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Booking cancelled.')]);

        return to_route('admin.bookings.show', $booking);
    }
}
