<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display a paginated, filterable list of payments.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'method' => $request->string('method')->toString() ?: null,
            'date' => $request->string('date')->toString() ?: null,
        ];

        $payments = Payment::query()
            ->with(['booking.user', 'booking.court'])
            ->when($filters['search'], fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($b) => $b
                        ->where('booking_reference', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")));
            }))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['method'], fn ($query, $method) => $query->where('payment_method', $method))
            ->when($filters['date'], fn ($query, $date) => $query->whereDate('created_at', $date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/payments/index', [
            'payments' => PaymentResource::collection($payments),
            'filters' => $filters,
            'statuses' => PaymentStatus::options(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    /**
     * Show a single payment's details.
     */
    public function show(Payment $payment): Response
    {
        $this->authorize('view', $payment);

        $payment->load(['booking.user', 'booking.court']);

        return Inertia::render('admin/payments/show', [
            'payment' => new PaymentResource($payment),
        ]);
    }
}
