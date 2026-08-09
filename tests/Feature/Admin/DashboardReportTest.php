<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->court = Court::factory()->create(['name' => 'Court A', 'is_active' => true]);
});

/**
 * Create a booking on a court/date with a snapshot amount and status.
 */
function bookingOn(Court $court, string $date, BookingStatus $status, int $amount = 3000, int $startHour = 8): Booking
{
    return Booking::factory()->for($court)->create([
        'booking_date' => $date,
        'status' => $status,
        'total_amount' => $amount,
        'start_time' => sprintf('%02d:00', $startHour),
        'end_time' => sprintf('%02d:00', $startHour + 1),
        'duration_hours' => 1,
    ]);
}

// ---------------------------------------------------------------------------
// Dashboard
// ---------------------------------------------------------------------------

test("today's bookings count only active bookings dated today", function () {
    $today = Carbon::today()->toDateString();
    bookingOn($this->court, $today, BookingStatus::Confirmed);
    bookingOn($this->court, $today, BookingStatus::PendingPayment);
    bookingOn($this->court, $today, BookingStatus::Cancelled); // excluded
    bookingOn($this->court, Carbon::tomorrow()->toDateString(), BookingStatus::Confirmed); // excluded

    $stats = app(DashboardService::class)->build()['stats'];

    expect($stats['today_bookings'])->toBe(2);
});

test("today's revenue only counts paid payments for bookings dated today", function () {
    $today = Carbon::today()->toDateString();
    $paid = bookingOn($this->court, $today, BookingStatus::Confirmed, 5000);
    Payment::factory()->for($paid)->paid()->create(['amount' => 5000]);

    // Non-paid and off-day payments must be excluded.
    $failed = bookingOn($this->court, $today, BookingStatus::Confirmed, 5000);
    Payment::factory()->for($failed)->failed()->create(['amount' => 5000]);
    Payment::factory()->for($failed)->create(['amount' => 5000]); // pending

    $tomorrow = bookingOn($this->court, Carbon::tomorrow()->toDateString(), BookingStatus::Confirmed);
    Payment::factory()->for($tomorrow)->paid()->create(['amount' => 9900]);

    $stats = app(DashboardService::class)->build()['stats'];

    expect($stats['today_revenue'])->toBe(50.0)
        ->and($stats['today_revenue_formatted'])->toBe('₱50.00');
});

test('active courts and pending payments are counted', function () {
    Court::factory()->count(2)->create(['is_active' => true]);
    Court::factory()->inactive()->create();

    $booking = bookingOn($this->court, Carbon::today()->toDateString(), BookingStatus::PendingPayment);
    Payment::factory()->for($booking)->create(); // pending
    Payment::factory()->for($booking)->paid()->create(); // excluded

    $stats = app(DashboardService::class)->build()['stats'];

    expect($stats['active_courts'])->toBe(3) // Court A + 2
        ->and($stats['pending_payments'])->toBe(1);
});

test('the dashboard renders live statistics, schedule and recent activity', function () {
    $booking = bookingOn($this->court, Carbon::today()->toDateString(), BookingStatus::Confirmed);
    Payment::factory()->for($booking)->paid()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/dashboard')
            ->has('stats')
            ->has('schedule.courts')
            ->has('recentBookings.data')
            ->has('recentPayments.data')
        );
});

// ---------------------------------------------------------------------------
// Reports
// ---------------------------------------------------------------------------

test('report revenue excludes failed, expired and pending payments', function () {
    $date = Carbon::today()->toDateString();
    $paid = bookingOn($this->court, $date, BookingStatus::Confirmed, 3000);
    Payment::factory()->for($paid)->paid()->create(['amount' => 3000]);

    $other = bookingOn($this->court, $date, BookingStatus::Confirmed, 3000);
    Payment::factory()->for($other)->failed()->create(['amount' => 3000]);
    Payment::factory()->for($other)->create(['amount' => 3000]); // pending
    Payment::factory()->for($other)->create(['amount' => 3000, 'status' => \App\Enums\PaymentStatus::Expired]);

    $report = app(ReportService::class);
    $range = $report->resolveRange('today');
    $summary = $report->build($range['from'], $range['to'])['summary'];

    expect($summary['total_revenue'])->toBe(30.0)
        ->and($summary['total_revenue_formatted'])->toBe('₱30.00');
});

test('report summary computes bookings, statuses and average value', function () {
    $date = Carbon::today()->toDateString();
    bookingOn($this->court, $date, BookingStatus::Confirmed, 4000);
    bookingOn($this->court, $date, BookingStatus::Confirmed, 2000);
    bookingOn($this->court, $date, BookingStatus::Cancelled, 6000);

    $report = app(ReportService::class);
    $range = $report->resolveRange('today');
    $summary = $report->build($range['from'], $range['to'])['summary'];

    expect($summary['total_bookings'])->toBe(3)
        ->and($summary['confirmed_bookings'])->toBe(2)
        ->and($summary['cancelled_bookings'])->toBe(1)
        // avg of 4000, 2000, 6000 = 4000 centavos = ₱40.00
        ->and($summary['average_booking_value'])->toBe(40.0);
});

test('revenue and bookings by court group correctly', function () {
    $courtB = Court::factory()->create(['name' => 'Court B']);
    $date = Carbon::today()->toDateString();

    $a1 = bookingOn($this->court, $date, BookingStatus::Confirmed, 3000);
    Payment::factory()->for($a1)->paid()->create(['amount' => 3000]);
    $a2 = bookingOn($this->court, $date, BookingStatus::Confirmed, 3000);
    Payment::factory()->for($a2)->paid()->create(['amount' => 3000]);
    $b1 = bookingOn($courtB, $date, BookingStatus::Confirmed, 5000);
    Payment::factory()->for($b1)->paid()->create(['amount' => 5000]);

    $report = app(ReportService::class);
    $range = $report->resolveRange('today');
    $data = $report->build($range['from'], $range['to']);

    $revenueByCourt = collect($data['revenue_by_court'])->keyBy('court');
    $bookingsByCourt = collect($data['bookings_by_court'])->keyBy('court');

    expect($revenueByCourt['Court A']['amount'])->toBe(60.0)
        ->and($revenueByCourt['Court B']['amount'])->toBe(50.0)
        ->and($bookingsByCourt['Court A']['count'])->toBe(2)
        ->and($bookingsByCourt['Court B']['count'])->toBe(1);
});

test('peak booking hours are aggregated by start time', function () {
    $date = Carbon::today()->toDateString();
    bookingOn($this->court, $date, BookingStatus::Confirmed, 3000, startHour: 8);
    bookingOn($this->court, $date, BookingStatus::Confirmed, 3000, startHour: 8);
    bookingOn($this->court, $date, BookingStatus::Confirmed, 3000, startHour: 18);

    $report = app(ReportService::class);
    $range = $report->resolveRange('today');
    $peak = collect($report->build($range['from'], $range['to'])['peak_hours'])->keyBy('hour');

    expect($peak['08:00']['count'])->toBe(2)
        ->and($peak['08:00']['label'])->toBe('8 AM')
        ->and($peak['18:00']['count'])->toBe(1);
});

test('date range filtering only includes bookings within the range', function () {
    $courtB = $this->court;
    $inRange = bookingOn($courtB, Carbon::today()->toDateString(), BookingStatus::Confirmed, 3000);
    Payment::factory()->for($inRange)->paid()->create(['amount' => 3000]);

    // Last month — outside "this month" for most of the month, so pin an explicit range instead.
    $outside = bookingOn($courtB, Carbon::today()->subMonths(2)->toDateString(), BookingStatus::Confirmed, 9900);
    Payment::factory()->for($outside)->paid()->create(['amount' => 9900]);

    $report = app(ReportService::class);
    $range = $report->resolveRange('today');
    $summary = $report->build($range['from'], $range['to'])['summary'];

    expect($summary['total_bookings'])->toBe(1)
        ->and($summary['total_revenue'])->toBe(30.0);
});

test('custom date ranges are resolved inclusively', function () {
    $report = app(ReportService::class);
    $range = $report->resolveRange('custom', '2026-08-01', '2026-08-31');

    expect($range['from']->toDateString())->toBe('2026-08-01')
        ->and($range['to']->toDateString())->toBe('2026-08-31')
        ->and($range['preset'])->toBe('custom');

    bookingOn($this->court, '2026-08-15', BookingStatus::Confirmed, 3000);
    bookingOn($this->court, '2026-09-01', BookingStatus::Confirmed, 3000); // outside

    $summary = $report->build($range['from'], $range['to'])['summary'];

    expect($summary['total_bookings'])->toBe(1);
});

test('the reports page renders for admins and is forbidden to customers', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/reports')
            ->has('report.summary')
            ->where('filters.range', 'month')
        );

    $this->actingAs(User::factory()->create())
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});
