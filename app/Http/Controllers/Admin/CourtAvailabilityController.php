<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourtBlockReason;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourtBlockResource;
use App\Http\Resources\CourtResource;
use App\Models\Court;
use App\Services\CourtAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CourtAvailabilityController extends Controller
{
    public function __construct(private CourtAvailabilityService $availability) {}

    /**
     * Show hourly availability for a court on a selected date.
     */
    public function index(Request $request, Court $court): Response
    {
        $this->authorize('update', $court);

        $date = $this->resolveDate($request);

        $blocks = $court->blocks()
            ->whereDate('block_date', $date->toDateString())
            ->orderBy('start_time')
            ->get();

        return Inertia::render('admin/courts/availability', [
            'court' => new CourtResource($court),
            'date' => $date->toDateString(),
            'availability' => $this->availability->forDate($court, $date),
            'blocks' => CourtBlockResource::collection($blocks),
            'blockReasons' => CourtBlockReason::options(),
        ]);
    }

    /**
     * Resolve the requested date, defaulting to today when absent or invalid.
     */
    private function resolveDate(Request $request): Carbon
    {
        $input = $request->query('date');

        if (is_string($input) && strtotime($input) !== false) {
            return Carbon::parse($input)->startOfDay();
        }

        return Carbon::today();
    }
}
