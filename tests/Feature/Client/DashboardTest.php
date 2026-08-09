<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create(['name' => 'Center Court']);
});

test('an authenticated customer can access their dashboard', function () {
    $this->actingAs($this->customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/dashboard')
            ->has('stats')
            ->has('recentBookings.data')
        );
});

test('a guest is redirected to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('a customer cannot access the admin area', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('the dashboard reflects only the current customer bookings', function () {
    // The signed-in customer's bookings.
    Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->addWeek()->toDateString(),
        'booking_reference' => 'PB-MINE-0001',
    ]);
    Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Completed,
        'booking_date' => now()->subWeek()->toDateString(),
    ]);

    // Another customer's booking that must never surface.
    $other = User::factory()->create();
    Booking::factory()->for($other)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => now()->addWeek()->toDateString(),
        'booking_reference' => 'PB-OTHER-9999',
    ]);

    $this->actingAs($this->customer)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.total_bookings', 2)
            ->where('stats.upcoming_bookings', 1)
            ->where('stats.completed_bookings', 1)
            ->where('upcomingBooking.data.booking_reference', 'PB-MINE-0001')
            ->has('recentBookings.data', 2)
        );
});

test('another customers bookings are never exposed in the payload', function () {
    $other = User::factory()->create();
    Booking::factory()->for($other)->for($this->court)->create([
        'booking_reference' => 'PB-SECRET-0001',
    ]);

    $response = $this->actingAs($this->customer)->get(route('dashboard'));

    expect($response->content())->not->toContain('PB-SECRET-0001');
});

test('a customer with no bookings sees an empty upcoming state', function () {
    $this->actingAs($this->customer)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('upcomingBooking', null)
            ->where('stats.total_bookings', 0)
            ->has('recentBookings.data', 0)
        );
});
