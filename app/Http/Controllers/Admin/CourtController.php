<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourtType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourtRequest;
use App\Http\Requests\Admin\UpdateCourtRequest;
use App\Http\Resources\CourtResource;
use App\Models\Court;
use App\Services\AuditLogger;
use App\Services\CourtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourtController extends Controller
{
    public function __construct(
        private CourtService $courts,
        private AuditLogger $audit,
    ) {}

    /**
     * Display a paginated, filterable list of courts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Court::class);

        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'type' => $request->string('type')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];

        $courts = Court::query()
            ->when($filters['search'], fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['type'], fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'], fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/courts/index', [
            'courts' => CourtResource::collection($courts),
            'filters' => $filters,
            'courtTypes' => CourtType::options(),
        ]);
    }

    /**
     * Show the form for creating a new court.
     */
    public function create(): Response
    {
        $this->authorize('create', Court::class);

        return Inertia::render('admin/courts/create', [
            'courtTypes' => CourtType::options(),
        ]);
    }

    /**
     * Persist a newly created court.
     */
    public function store(StoreCourtRequest $request): RedirectResponse
    {
        $court = $this->courts->create($request->validated(), $request->file('image'));

        $this->audit->log('court.created', $court, ['name' => $court->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court created.')]);

        return to_route('admin.courts.index');
    }

    /**
     * Show the form for editing an existing court.
     */
    public function edit(Court $court): Response
    {
        $this->authorize('update', $court);

        return Inertia::render('admin/courts/edit', [
            'court' => new CourtResource($court),
            'courtTypes' => CourtType::options(),
        ]);
    }

    /**
     * Update an existing court.
     */
    public function update(UpdateCourtRequest $request, Court $court): RedirectResponse
    {
        $this->courts->update($court, $request->validated(), $request->file('image'));

        $this->audit->log('court.updated', $court, ['name' => $court->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court updated.')]);

        return to_route('admin.courts.index');
    }

    /**
     * Toggle a court between active and inactive.
     */
    public function toggleStatus(Court $court): RedirectResponse
    {
        $this->authorize('update', $court);

        $court = $this->courts->toggleStatus($court);

        $this->audit->log($court->is_active ? 'court.enabled' : 'court.disabled', $court, [
            'name' => $court->name,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $court->is_active ? __('Court activated.') : __('Court deactivated.'),
        ]);

        return back();
    }
}
