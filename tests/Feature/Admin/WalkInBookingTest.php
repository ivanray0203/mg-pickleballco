<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->court = Court::factory()->create([
        'opening_time' => '06:00',
        'closing_time' => '22:00',
        'hourly_rate' => 3000,
    ]);

    // Stub the PayMongo hosted-checkout call used by the GCash path.
    config()->set('services.payment.driver', 'paymongo');
    config()->set('services.paymongo.secret_key', 'sk_test_123');
    config()->set('services.paymongo.webhook_secret', 'whsec_test_123');
    $this->app->forgetInstance(PaymentGateway::class);

    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_test_walkin',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.test/cs_test_walkin'],
            ],
        ], 200),
    ]);
});

function walkInPayload(array $overrides = []): array
{
    return array_merge([
        'customer' => ['mode' => 'guest', 'name' => 'Juan Dela Cruz', 'phone' => '09171234567'],
        'court_id' => test()->court->id,
        'booking_date' => now()->addDay()->toDateString(),
        'start_time' => '08:00',
        'duration' => 2,
        'payment_method' => PaymentMethod::Cash->value,
    ], $overrides);
}

test('the walk-in form is only reachable by admins', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.bookings.walk-in.create'))
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.walk-in.create'))
        ->assertOk();
});

test('selecting a court returns open availability with bookable slots', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.bookings.walk-in.create', [
            'court_id' => $this->court->id,
            'date' => now()->addDay()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('availability.is_open', true)
            ->where('availability.bookable_date', true)
            ->has('availability.slots', 16)
        );
});

test('an inactive court yields no availability', function () {
    $this->court->update(['is_active' => false]);

    $this->actingAs($this->admin)
        ->get(route('admin.bookings.walk-in.create', ['court_id' => $this->court->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('availability', null));
});

test('a cash walk-in creates a confirmed booking, a guest, and a paid payment', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.bookings.walk-in.store'), walkInPayload())
        ->assertRedirect();

    $booking = Booking::query()->latest('id')->firstOrFail();

    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->total_amount)->toBe(6000) // 3000 * 2 hours
        ->and($booking->user->is_guest)->toBeTrue()
        ->and($booking->user->name)->toBe('Juan Dela Cruz')
        ->and($booking->user->phone)->toBe('09171234567');

    expect($booking->payments()->first())
        ->status->toBe(PaymentStatus::Paid)
        ->payment_method->toBe(PaymentMethod::Cash);
});

test('a cash walk-in with no email sends no email but records the in-app notification', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.bookings.walk-in.store'), walkInPayload());

    $guest = User::query()->where('is_guest', true)->firstOrFail();

    Notification::assertSentTo($guest, BookingConfirmed::class, function ($notification) use ($guest) {
        return $notification->via($guest) === ['database'];
    });
});

test('a cash walk-in with an email is eligible for the approved email', function () {
    Notification::fake();
    config()->set('mail.notifications.email_enabled', true);

    $this->actingAs($this->admin)
        ->post(route('admin.bookings.walk-in.store'), walkInPayload([
            'customer' => ['mode' => 'guest', 'name' => 'Ana Cruz', 'email' => 'ana@example.com'],
        ]));

    $guest = User::query()->where('email', 'ana@example.com')->firstOrFail();

    Notification::assertSentTo($guest, BookingConfirmed::class, function ($notification) use ($guest) {
        return $notification->via($guest) === ['database', 'mail'];
    });
});

test('a walk-in can be attached to an existing customer', function () {
    Notification::fake();
    $customer = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.bookings.walk-in.store'), walkInPayload([
            'customer' => ['mode' => 'existing', 'user_id' => $customer->id],
        ]))
        ->assertRedirect();

    $booking = Booking::query()->latest('id')->firstOrFail();

    expect($booking->user_id)->toBe($customer->id);
    expect(User::query()->where('is_guest', true)->count())->toBe(0);
});

test('a gcash walk-in creates a pending booking and a pending payment', function () {
    Notification::fake();

    // Posted as an Inertia visit, so Inertia::location() returns its external
    // 409 redirect rather than a plain RedirectResponse.
    $this->actingAs($this->admin)
        ->withHeader('X-Inertia', 'true')
        ->post(route('admin.bookings.walk-in.store'), walkInPayload([
            'payment_method' => PaymentMethod::GCash->value,
        ]))
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'https://checkout.paymongo.test/cs_test_walkin');

    $booking = Booking::query()->latest('id')->firstOrFail();

    expect($booking->status)->toBe(BookingStatus::PendingPayment);
    expect($booking->payments()->first()->status)->toBe(PaymentStatus::Pending);

    // No confirmation is sent until the provider webhook settles the payment.
    Notification::assertNotSentTo($booking->user, BookingConfirmed::class);
});

test('an overlapping walk-in slot is rejected', function () {
    Notification::fake();

    Booking::factory()->for($this->court)->create([
        'booking_date' => now()->addDay()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '10:00',
        'status' => BookingStatus::Confirmed,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.bookings.walk-in.store'), walkInPayload([
            'start_time' => '09:00',
            'duration' => 1,
        ]))
        ->assertSessionHasErrors('start_time');
});

test('guest mode requires a name and existing mode requires a customer', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.bookings.walk-in.store'), walkInPayload([
            'customer' => ['mode' => 'guest'],
        ]))
        ->assertSessionHasErrors('customer.name');

    $this->actingAs($this->admin)
        ->post(route('admin.bookings.walk-in.store'), walkInPayload([
            'customer' => ['mode' => 'existing'],
        ]))
        ->assertSessionHasErrors('customer.user_id');
});
