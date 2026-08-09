<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private CustomerService $customers) {}

    /**
     * Show the signed-in customer their own booking overview.
     *
     * Reuses the shared {@see CustomerService} so the same aggregate queries
     * power both the admin customer view and the customer's own dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $upcoming = $this->customers->upcomingBookings($user);

        return Inertia::render('client/dashboard', [
            'stats' => $this->customers->stats($user),
            'upcomingBooking' => $upcoming->isNotEmpty()
                ? new BookingResource($upcoming->first())
                : null,
            'recentBookings' => BookingResource::collection(
                $this->customers->bookingHistory($user, 5),
            ),
        ]);
    }
}
