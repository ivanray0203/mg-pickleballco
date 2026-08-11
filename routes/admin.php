<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CourtAvailabilityController;
use App\Http\Controllers\Admin\CourtBlockController;
use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\CourtScheduleController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DailyScheduleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\OpenPlayController;
use App\Http\Controllers\Admin\OpenPlayGameController;
use App\Http\Controllers\Admin\OpenPlayGameSubmissionController;
use App\Http\Controllers\Admin\OpenPlayMatchupController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SeasonController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\WalkInBookingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::redirect('/', '/admin/dashboard');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        Route::patch('courts/{court}/toggle-status', [CourtController::class, 'toggleStatus'])
            ->name('courts.toggle-status');

        // Schedule & availability management.
        Route::get('courts/{court}/schedule', [CourtScheduleController::class, 'edit'])
            ->name('courts.schedule.edit');
        Route::put('courts/{court}/schedule', [CourtScheduleController::class, 'update'])
            ->name('courts.schedule.update');

        Route::get('courts/{court}/availability', [CourtAvailabilityController::class, 'index'])
            ->name('courts.availability.index');

        Route::post('courts/{court}/blocks', [CourtBlockController::class, 'store'])
            ->name('courts.blocks.store');
        Route::delete('courts/{court}/blocks/{block}', [CourtBlockController::class, 'destroy'])
            ->scopeBindings()
            ->name('courts.blocks.destroy');

        Route::resource('courts', CourtController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        // Rentable equipment (paddles, balls, etc.). Toggle-status declared
        // before the resource so it isn't shadowed by resource routes.
        Route::patch('equipment/{equipment}/toggle-status', [EquipmentController::class, 'toggleStatus'])
            ->name('equipment.toggle-status');

        Route::resource('equipment', EquipmentController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);

        // Open play sessions. Custom actions declared before the resource so
        // they aren't shadowed by the {openPlaySession} route.
        Route::patch('open-play/{openPlaySession}/cancel', [OpenPlayController::class, 'cancel'])
            ->name('open-play.cancel');

        // Session-day console: check-in & game recording.
        Route::get('open-play/{openPlaySession}/manage', [OpenPlayController::class, 'manage'])
            ->name('open-play.manage');
        Route::patch('open-play/{openPlaySession}/complete', [OpenPlayController::class, 'complete'])
            ->name('open-play.complete');
        Route::patch('open-play/registrations/{registration}/check-in', [OpenPlayController::class, 'checkIn'])
            ->name('open-play.registrations.check-in');
        Route::delete('open-play/registrations/{registration}/check-in', [OpenPlayController::class, 'undoCheckIn'])
            ->name('open-play.registrations.undo-check-in');
        Route::post('open-play/{openPlaySession}/games', [OpenPlayGameController::class, 'store'])
            ->name('open-play.games.store');
        Route::delete('open-play/games/{game}', [OpenPlayGameController::class, 'destroy'])
            ->name('open-play.games.destroy');

        // Player self-reported results awaiting organizer review.
        Route::patch('open-play/submissions/{submission}/confirm', [OpenPlayGameSubmissionController::class, 'confirm'])
            ->name('open-play.submissions.confirm');
        Route::delete('open-play/submissions/{submission}', [OpenPlayGameSubmissionController::class, 'reject'])
            ->name('open-play.submissions.reject');

        // Auto-paired match queue (shuffle into 2v2, play one at a time).
        Route::post('open-play/{openPlaySession}/matchups/generate', [OpenPlayMatchupController::class, 'generate'])
            ->name('open-play.matchups.generate');
        Route::delete('open-play/{openPlaySession}/matchups', [OpenPlayMatchupController::class, 'clear'])
            ->name('open-play.matchups.clear');
        Route::patch('open-play/matchups/{matchup}/record', [OpenPlayMatchupController::class, 'record'])
            ->name('open-play.matchups.record');
        Route::patch('open-play/matchups/{matchup}/court', [OpenPlayMatchupController::class, 'assignCourt'])
            ->name('open-play.matchups.court');
        Route::delete('open-play/matchups/{matchup}', [OpenPlayMatchupController::class, 'destroy'])
            ->name('open-play.matchups.destroy');

        Route::resource('open-play', OpenPlayController::class)
            ->parameters(['open-play' => 'openPlaySession'])
            ->only(['index', 'create', 'store', 'edit', 'update']);

        // Season leaderboards. Leaderboard declared before the resource so it
        // isn't shadowed by the {season} route.
        Route::get('seasons/{season}/leaderboard', [SeasonController::class, 'leaderboard'])
            ->name('seasons.leaderboard');

        Route::resource('seasons', SeasonController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);

        // Booking management.
        Route::get('schedule', [DailyScheduleController::class, 'index'])->name('schedule.index');

        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        // Walk-in booking creation. Declared before the {booking} route so the
        // "walk-in" segment isn't captured as a booking id.
        Route::get('bookings/walk-in', [WalkInBookingController::class, 'create'])->name('bookings.walk-in.create');
        Route::post('bookings/walk-in', [WalkInBookingController::class, 'store'])->name('bookings.walk-in.store');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])
            ->name('bookings.cancel');

        // Payment monitoring (read-only; settlement is driven by the provider).
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');

        // Customer management (read-only operational visibility).
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        // Business settings.
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
