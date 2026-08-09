<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCourtScheduleRequest;
use App\Http\Resources\CourtResource;
use App\Http\Resources\CourtScheduleResource;
use App\Models\Court;
use App\Services\AuditLogger;
use App\Services\CourtScheduleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourtScheduleController extends Controller
{
    public function __construct(
        private CourtScheduleService $schedules,
        private AuditLogger $audit,
    ) {}

    /**
     * Show the weekly schedule editor for a court.
     */
    public function edit(Court $court): Response
    {
        $this->authorize('update', $court);

        return Inertia::render('admin/courts/schedule', [
            'court' => new CourtResource($court),
            'schedules' => CourtScheduleResource::collection($this->schedules->getOrCreateWeek($court)),
        ]);
    }

    /**
     * Persist the weekly schedule.
     */
    public function update(UpdateCourtScheduleRequest $request, Court $court): RedirectResponse
    {
        /** @var array{days: array<int, array{day_of_week: int, is_open: bool, opening_time: ?string, closing_time: ?string}>} $validated */
        $validated = $request->validated();

        $this->schedules->sync($court, $validated['days']);

        $this->audit->log('court.schedule_updated', $court, ['name' => $court->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Schedule updated.')]);

        return to_route('admin.courts.schedule.edit', $court);
    }
}
