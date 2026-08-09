<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create(['name' => 'Center Court']);
});

test('admin can view the booking list', function () {
    Booking::factory()->count(3)->for($this->court)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/bookings/index')
            ->has('bookings.data', 3)
            ->has('courts')
            ->has('statuses')
        );
});

test('the booking list paginates', function () {
    Booking::factory()->count(20)->for($this->court)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('bookings.data', 15)
            ->where('bookings.meta.total', 20)
        );
});

test('bookings can be filtered by status', function () {
    Booking::factory()->for($this->court)->create(['status' => BookingStatus::Confirmed]);
    Booking::factory()->for($this->court)->cancelled()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.index', ['status' => BookingStatus::Cancelled->value]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.status', BookingStatus::Cancelled->value)
        );
});

test('bookings can be filtered by court', function () {
    $other = Court::factory()->create();
    Booking::factory()->count(2)->for($this->court)->create();
    Booking::factory()->for($other)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.index', ['court' => $this->court->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('bookings.data', 2));
});

test('bookings can be filtered by date', function () {
    Booking::factory()->for($this->court)->create(['booking_date' => '2026-08-05']);
    Booking::factory()->for($this->court)->create(['booking_date' => '2026-08-06']);

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.index', ['date' => '2026-08-05']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('bookings.data', 1));
});

test('bookings can be searched by reference or customer name', function () {
    $alice = User::factory()->create(['name' => 'Alice Angeles']);
    Booking::factory()->for($this->court)->for($alice)->create();
    Booking::factory()->for($this->court)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.index', ['search' => 'Alice']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.customer.name', 'Alice Angeles')
        );
});

test('admin can view booking details', function () {
    $booking = Booking::factory()->for($this->court)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/bookings/show')
            ->where('booking.data.booking_reference', $booking->booking_reference)
            ->where('booking.data.status', $booking->status->value)
        );
});

test('customers cannot access booking management', function () {
    $booking = Booking::factory()->for($this->court)->create();

    $this->actingAs($this->customer)
        ->get(route('admin.bookings.index'))
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->get(route('admin.bookings.show', $booking))
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->patch(route('admin.bookings.cancel', $booking), ['cancellation_reason' => 'nope'])
        ->assertForbidden();
});

test('guests are redirected from booking management', function () {
    $this->get(route('admin.bookings.index'))->assertRedirect(route('login'));
});

test('admin can cancel an eligible booking', function () {
    $booking = Booking::factory()->for($this->court)->create(['status' => BookingStatus::Confirmed]);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.cancel', $booking), [
            'cancellation_reason' => 'Customer requested a refund.',
        ])
        ->assertRedirect(route('admin.bookings.show', $booking));

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->cancelled_at)->not->toBeNull()
        ->and($booking->cancellation_reason)->toBe('Customer requested a refund.');
});

test('cancellation requires a reason', function () {
    $booking = Booking::factory()->for($this->court)->create(['status' => BookingStatus::Confirmed]);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.cancel', $booking), ['cancellation_reason' => ''])
        ->assertSessionHasErrors('cancellation_reason');

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

test('an already-cancelled booking cannot be cancelled again', function () {
    $booking = Booking::factory()->for($this->court)->cancelled()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.cancel', $booking), [
            'cancellation_reason' => 'Trying again.',
        ])
        ->assertForbidden();
});

test('cancelling never hard-deletes the booking', function () {
    $booking = Booking::factory()->for($this->court)->create(['status' => BookingStatus::Confirmed]);

    $this->actingAs($this->admin)
        ->patch(route('admin.bookings.cancel', $booking), [
            'cancellation_reason' => 'Weather.',
        ]);

    expect(Booking::whereKey($booking->id)->exists())->toBeTrue();
});

test('admin can view the daily schedule across courts', function () {
    $courtA = Court::factory()->create(['name' => 'Court A']);
    Court::factory()->create(['name' => 'Court B']);

    Booking::factory()->forSlot($courtA, '2026-08-05', 8, 9)->create([
        'status' => BookingStatus::Confirmed,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.schedule.index', ['date' => '2026-08-05']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/schedule')
            ->where('date', '2026-08-05')
            ->has('schedule.courts', 3)
            ->where('schedule.courts', function ($courts) {
                $courtA = collect($courts)->firstWhere('name', 'Court A');

                return $courtA['slots'][0]['label'] === '8:00 AM'
                    && $courtA['slots'][0]['status'] === 'booked';
            })
        );
});

test('customers cannot view the daily schedule', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.schedule.index'))
        ->assertForbidden();
});
