<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EquipmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEquipmentRequest;
use App\Http\Requests\Admin\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use App\Services\AuditLogger;
use App\Services\EquipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    public function __construct(
        private EquipmentService $equipment,
        private AuditLogger $audit,
    ) {}

    /**
     * Display a paginated, filterable list of equipment.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Equipment::class);

        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'type' => $request->string('type')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];

        $equipment = Equipment::query()
            ->when($filters['search'], fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['type'], fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'], fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/equipment/index', [
            'equipment' => EquipmentResource::collection($equipment),
            'filters' => $filters,
            'equipmentTypes' => EquipmentType::options(),
        ]);
    }

    /**
     * Show the form for creating a new equipment item.
     */
    public function create(): Response
    {
        $this->authorize('create', Equipment::class);

        return Inertia::render('admin/equipment/create', [
            'equipmentTypes' => EquipmentType::options(),
        ]);
    }

    /**
     * Persist a newly created equipment item.
     */
    public function store(StoreEquipmentRequest $request): RedirectResponse
    {
        $equipment = $this->equipment->create($request->validated(), $request->file('image'));

        $this->audit->log('equipment.created', $equipment, ['name' => $equipment->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment created.')]);

        return to_route('admin.equipment.index');
    }

    /**
     * Show the form for editing an existing equipment item.
     */
    public function edit(Equipment $equipment): Response
    {
        $this->authorize('update', $equipment);

        return Inertia::render('admin/equipment/edit', [
            'equipment' => new EquipmentResource($equipment),
            'equipmentTypes' => EquipmentType::options(),
        ]);
    }

    /**
     * Update an existing equipment item.
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $this->equipment->update($equipment, $request->validated(), $request->file('image'));

        $this->audit->log('equipment.updated', $equipment, ['name' => $equipment->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment updated.')]);

        return to_route('admin.equipment.index');
    }

    /**
     * Toggle an equipment item between active and inactive.
     */
    public function toggleStatus(Equipment $equipment): RedirectResponse
    {
        $this->authorize('update', $equipment);

        $equipment = $this->equipment->toggleStatus($equipment);

        $this->audit->log($equipment->is_active ? 'equipment.enabled' : 'equipment.disabled', $equipment, [
            'name' => $equipment->name,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $equipment->is_active ? __('Equipment activated.') : __('Equipment deactivated.'),
        ]);

        return back();
    }
}
