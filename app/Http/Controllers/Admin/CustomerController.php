<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\PaymentResource;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customers) {}

    /**
     * Display a paginated, searchable list of customers with aggregates.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->toString() ?: null;

        return Inertia::render('admin/customers/index', [
            'customers' => CustomerResource::collection($this->customers->paginateList($search)),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show a single customer with statistics and history.
     */
    public function show(User $customer): Response
    {
        $this->authorize('view', $customer);

        return Inertia::render('admin/customers/show', [
            'customer' => new CustomerResource($customer),
            'stats' => $this->customers->stats($customer),
            'upcomingBookings' => BookingResource::collection($this->customers->upcomingBookings($customer)),
            'bookingHistory' => BookingResource::collection($this->customers->bookingHistory($customer)),
            'paymentHistory' => PaymentResource::collection($this->customers->paymentHistory($customer)),
        ]);
    }
}
