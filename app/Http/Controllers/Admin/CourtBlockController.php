<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourtBlockRequest;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CourtBlockController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Store a new court block.
     */
    public function store(StoreCourtBlockRequest $request, Court $court): RedirectResponse
    {
        $block = $court->blocks()->create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
        ]);

        $this->audit->log('court.blocked', $block, [
            'court' => $court->name,
            'date' => $block->block_date->toDateString(),
            'reason' => $block->reason->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court block added.')]);

        return to_route('admin.courts.availability.index', [
            'court' => $court,
            'date' => $request->string('block_date')->toString(),
        ]);
    }

    /**
     * Remove a court block.
     */
    public function destroy(Court $court, CourtBlock $block): RedirectResponse
    {
        $this->authorize('update', $court);

        $date = $block->block_date->toDateString();

        $block->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court block removed.')]);

        return to_route('admin.courts.availability.index', [
            'court' => $court,
            'date' => $date,
        ]);
    }
}
