<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create(['name' => 'Alice Angeles', 'email' => 'alice@example.com']);
    $this->court = Court::factory()->create();
});

test('admin can list customers', function () {
    User::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/customers/index')
            // 4 customers; the admin account is excluded.
            ->has('customers.data', 4)
        );
});

test('the customer list excludes admin accounts', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.customers.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.email', 'alice@example.com')
        );
});

test('customers can be searched by name or email', function () {
    User::factory()->create(['name' => 'Bob Bautista', 'email' => 'bob@example.com']);

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index', ['search' => 'alice']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Alice Angeles')
        );

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index', ['search' => 'bob@example']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Bob Bautista')
        );
});

test('the customer list paginates', function () {
    User::factory()->count(20)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('customers.data', 15)
            ->where('customers.meta.total', 21)
        );
});

test('the customer list aggregates bookings and paid totals without leaking sensitive fields', function () {
    Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->addWeek()->toDateString(),
    ]);
    $paid = Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Completed,
        'booking_date' => now()->subWeek()->toDateString(),
    ]);
    Payment::factory()->for($paid)->paid()->create(['amount' => 3000]);
    Payment::factory()->for($paid)->failed()->create(['amount' => 3000]);

    $this->actingAs($this->admin)
        ->get(route('admin.customers.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('customers.data.0.total_bookings', 2)
            ->where('customers.data.0.upcoming_bookings', 1)
            ->where('customers.data.0.total_paid_amount', 30)
            ->missing('customers.data.0.password')
            ->missing('customers.data.0.two_factor_secret')
            ->missing('customers.data.0.remember_token')
        );
});

test('admin can view customer statistics and history', function () {
    Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->addWeek()->toDateString(),
    ]);
    Booking::factory()->for($this->customer)->for($this->court)->create(['status' => BookingStatus::Completed]);
    Booking::factory()->for($this->customer)->for($this->court)->cancelled()->create();
    $paid = Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->subWeek()->toDateString(),
    ]);
    Payment::factory()->for($paid)->paid()->create(['amount' => 4500]);

    $this->actingAs($this->admin)
        ->get(route('admin.customers.show', $this->customer))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/customers/show')
            ->where('customer.data.email', 'alice@example.com')
            ->where('stats.total_bookings', 4)
            ->where('stats.upcoming_bookings', 1)
            ->where('stats.completed_bookings', 1)
            ->where('stats.cancelled_bookings', 1)
            ->where('stats.total_paid_amount', 4500)
            ->has('upcomingBookings.data', 1)
            ->has('bookingHistory.data', 4)
            ->has('paymentHistory.data', 1)
        );
});

test('customer detail does not expose sensitive attributes', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.customers.show', $this->customer))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('customer.data.password')
            ->missing('customer.data.two_factor_secret')
            ->missing('customer.data.two_factor_recovery_codes')
            ->missing('customer.data.remember_token')
        );
});

test('an admin account cannot be viewed through customer management', function () {
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.customers.show', $otherAdmin))
        ->assertForbidden();
});

test('customers cannot access customer management', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.customers.index'))
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->get(route('admin.customers.show', $this->customer))
        ->assertForbidden();
});

test('guests are redirected from customer management', function () {
    $this->get(route('admin.customers.index'))->assertRedirect(route('login'));
});
