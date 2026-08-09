<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * Display the admin dashboard with live statistics, today's schedule and
     * recent activity.
     */
    public function index(): Response
    {
        return Inertia::render('admin/dashboard', $this->dashboard->build());
    }
}
