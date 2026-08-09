<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    /**
     * Display MVP reports for the selected date range.
     */
    public function index(Request $request): Response
    {
        $range = $this->reports->resolveRange(
            $request->string('range', 'month')->toString(),
            $request->string('from')->toString() ?: null,
            $request->string('to')->toString() ?: null,
        );

        return Inertia::render('admin/reports', [
            'report' => $this->reports->build($range['from'], $range['to']),
            'filters' => [
                'range' => $range['preset'],
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
        ]);
    }
}
