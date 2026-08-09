<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\DailyScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DailyScheduleController extends Controller
{
    public function __construct(private DailyScheduleService $schedule) {}

    /**
     * Show the day's availability across all active courts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Booking::class);

        $date = $this->resolveDate($request);

        return Inertia::render('admin/schedule', [
            'date' => $date->toDateString(),
            'schedule' => $this->schedule->boardForDate($date),
        ]);
    }

    private function resolveDate(Request $request): Carbon
    {
        $input = $request->query('date');

        if (is_string($input) && strtotime($input) !== false) {
            return Carbon::parse($input)->startOfDay();
        }

        return Carbon::today();
    }
}
