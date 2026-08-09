<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create(['name' => 'Center Court']);
});

// --- My Bookings list -------------------------------------------------------

test('a customer sees only their own bookings', function () {
    Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => Carbon::today()->addWeek()->toDateString(),
    ]);

    $other = User::factory()->create();
    Booking::factory()->for($other)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => Carbon::today()->addWeek()->toDateString(),
        'booking_reference' => 'PB-OTHER-0001',
    ]);

    $response = $this->actingAs($this->customer)->get(route('bookings.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('client/bookings')
        ->has('bookings.data', 1)
        ->where('filter', 'upcoming')
    );

    expect($response->content())->not->toContain('PB-OTHER-0001');
});

test('the upcoming filter shows active future bookings', function () {
    Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => Carbon::today()->addWeek()->toDateString(),
    ]);
    Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Completed,
        'booking_date' => Carbon::today()->subWeek()->toDateString(),
    ]);

    $this->actingAs($this->customer)
        ->get(route('bookings.index', ['filter' => 'upcoming']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('bookings.data', 1));
});

test('the completed filter shows completed bookings', function () {
    Booking::factory()->for($this->customer)->for($this->court)->create(['status' => BookingStatus::Completed]);
    Booking::factory()->for($this->customer)->for($this->court)->create(['status' => BookingStatus::Confirmed]);

    $this->actingAs($this->customer)
        ->get(route('bookings.index', ['filter' => 'completed']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.status', BookingStatus::Completed->value)
        );
});

test('the cancelled filter shows cancelled bookings', function () {
    Booking::factory()->for($this->customer)->for($this->court)->cancelled()->create();
    Booking::factory()->for($this->customer)->for($this->court)->create(['status' => BookingStatus::Confirmed]);

    $this->actingAs($this->customer)
        ->get(route('bookings.index', ['filter' => 'cancelled']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.status', BookingStatus::Cancelled->value)
        );
});

test('the expired filter shows expired bookings', function () {
    Booking::factory()->for($this->customer)->for($this->court)->create(['status' => BookingStatus::Expired]);
    Booking::factory()->for($this->customer)->for($this->court)->create(['status' => BookingStatus::Confirmed]);

    $this->actingAs($this->customer)
        ->get(route('bookings.index', ['filter' => 'expired']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.status', BookingStatus::Expired->value)
        );
});

test('the bookings list paginates', function () {
    Booking::factory()->count(14)->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => Carbon::today()->addWeek()->toDateString(),
    ]);

    $this->actingAs($this->customer)
        ->get(route('bookings.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('bookings.data', 10)
            ->where('bookings.meta.total', 14)
        );
});

test('the bookings list exposes the latest payment status', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'booking_date' => Carbon::today()->addWeek()->toDateString(),
    ]);
    Payment::factory()->for($booking)->paid()->create();

    $this->actingAs($this->customer)
        ->get(route('bookings.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('bookings.data.0.payment_status', PaymentStatus::Paid->value)
        );
});

// --- Booking details --------------------------------------------------------

test('a customer can view their own booking details', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->court)->create([
        'status' => BookingStatus::Confirmed,
        'hourly_rate' => 40000,
        'total_amount' => 80000,
    ]);

    $this->actingAs($this->customer)
        ->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/booking-detail')
            ->where('booking.data.hourly_rate_formatted', '₱400.00')
            ->where('booking.data.total_amount_formatted', '₱800.00')
            ->where('expired', false)
        );
});

test('a customer cannot view another customers booking', function () {
    $booking = Booking::factory()->for(User::factory())->for($this->court)->create();

    $this->actingAs($this->customer)
        ->get(route('bookings.show', $booking))
        ->assertForbidden();
});

test('a pending, non-expired booking is payable from its details', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->court)->pending()->create([
        'expires_at' => Carbon::now()->addMinutes(5),
    ]);

    $this->actingAs($this->customer)
        ->get(route('bookings.show', $booking))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('booking.data.status', BookingStatus::PendingPayment->value)
            ->where('expired', false)
        );
});

test('an expired pending booking is flagged expired', function () {
    $booking = Booking::factory()->for($this->customer)->for($this->court)->pending()->create([
        'expires_at' => Carbon::now()->subMinute(),
    ]);

    $this->actingAs($this->customer)
        ->get(route('bookings.show', $booking))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('expired', true));
});

test('a guest is redirected from bookings', function () {
    $this->get(route('bookings.index'))->assertRedirect(route('login'));
});
