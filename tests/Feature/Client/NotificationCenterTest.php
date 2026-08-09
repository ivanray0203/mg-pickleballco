<?php

use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create();
    $this->booking = Booking::factory()->for($this->customer)->for($this->court)->create();
});

test('a guest is redirected from the notification center', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});

test('a customer sees only their own notifications', function () {
    $this->customer->notify(new BookingConfirmed($this->booking));

    $other = User::factory()->create();
    $otherBooking = Booking::factory()->for($other)->for($this->court)->create();
    $other->notify(new BookingConfirmed($otherBooking));

    $this->actingAs($this->customer)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/notifications')
            ->has('notifications.data', 1)
            ->where('unread_count', 1)
        );
});

test('the notification center paginates', function () {
    foreach (range(1, 18) as $ignored) {
        $this->customer->notify(new BookingConfirmed($this->booking));
    }

    $this->actingAs($this->customer)
        ->get(route('notifications.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('notifications.data', 15)
            ->where('notifications.meta.total', 18)
        );
});

test('the unread count is shared to every page', function () {
    $this->customer->notify(new BookingConfirmed($this->booking));

    $this->actingAs($this->customer)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('unreadNotifications', 1)
        );
});

test('a customer can mark a single notification as read', function () {
    $this->customer->notify(new BookingConfirmed($this->booking));
    $notification = $this->customer->notifications()->first();

    expect($notification->read_at)->toBeNull();

    $this->actingAs($this->customer)
        ->patch(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($this->customer->fresh()->unreadNotifications()->count())->toBe(0);
});

test('a customer can mark all notifications as read', function () {
    foreach (range(1, 3) as $ignored) {
        $this->customer->notify(new BookingConfirmed($this->booking));
    }

    expect($this->customer->unreadNotifications()->count())->toBe(3);

    $this->actingAs($this->customer)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    expect($this->customer->fresh()->unreadNotifications()->count())->toBe(0);
});

test('a customer cannot mark another customers notification as read', function () {
    $other = User::factory()->create();
    $otherBooking = Booking::factory()->for($other)->for($this->court)->create();
    $other->notify(new BookingConfirmed($otherBooking));
    $foreignNotification = $other->notifications()->first();

    $this->actingAs($this->customer)
        ->patch(route('notifications.read', $foreignNotification->id))
        ->assertNotFound();

    expect($other->fresh()->unreadNotifications()->count())->toBe(1);
});
