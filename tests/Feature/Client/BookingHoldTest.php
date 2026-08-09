<?php

use App\Enums\BookingStatus;
use App\Enums\CourtBlockReason;
use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CourtAvailabilityService;
use App\Settings\AppSettings;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create([
        'hourly_rate' => 40000, // ₱400.00
        'opening_time' => '06:00',
        'closing_time' => '22:00',
    ]);

    foreach (DayOfWeek::cases() as $day) {
        CourtSchedule::factory()->for($this->court)->create([
            'day_of_week' => $day,
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_open' => true,
        ]);
    }

    $this->date = Carbon::today()->addDays(2)->toDateString();
});

/**
 * @return array<string, mixed>
 */
function holdPayload(array $overrides = []): array
{
    return array_merge([
        'court' => test()->court->slug,
        'date' => test()->date,
        'start_time' => '15:00',
        'duration' => 2,
    ], $overrides);
}

// --- Review -----------------------------------------------------------------

test('the review page derives pricing on the server', function () {
    $this->actingAs($this->customer)
        ->get(route('book.review', holdPayload()))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/booking-review')
            ->where('summary.start_time', '15:00')
            ->where('summary.end_time', '17:00')
            ->where('summary.duration_hours', 2)
            ->where('summary.total_amount_formatted', '₱800.00')
        );
});

test('the review page redirects when the selection is unavailable', function () {
    Booking::factory()->for($this->court)->create([
        'booking_date' => $this->date,
        'start_time' => '15:00',
        'end_time' => '16:00',
        'status' => BookingStatus::Confirmed,
    ]);

    $this->actingAs($this->customer)
        ->get(route('book.review', holdPayload()))
        ->assertRedirect(route('book', ['court' => $this->court->slug, 'date' => $this->date]));
});

// --- Hold creation ----------------------------------------------------------

test('a customer can create a valid pending-payment hold', function () {
    app(AppSettings::class)->update(['booking_hold_minutes' => 10]);

    $this->actingAs($this->customer)
        ->post(route('bookings.store'), holdPayload());

    $booking = Booking::first();

    expect($booking)->not->toBeNull()
        ->and($booking->status)->toBe(BookingStatus::PendingPayment)
        ->and($booking->user_id)->toBe($this->customer->id)
        ->and($booking->duration_hours)->toBe(2)
        ->and($booking->end_time)->toStartWith('17:00')
        ->and($booking->expires_at)->not->toBeNull()
        ->and($booking->expires_at->gt(Carbon::now()))->toBeTrue();
});

test('the total is calculated server-side and ignores client input', function () {
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), holdPayload([
            'total_amount' => 1, // attempt to spoof — must be ignored
            'hourly_rate' => 1,
            'end_time' => '23:00',
        ]));

    $booking = Booking::first();

    // 2 hours × ₱400 (40000 centavos) = 80000 centavos, end derived as 17:00.
    expect($booking->total_amount)->toBe(80000)
        ->and($booking->hourly_rate)->toBe(40000)
        ->and($booking->end_time)->toStartWith('17:00');
});

test('the hold redirects to the booking page', function () {
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), holdPayload())
        ->assertRedirect(route('bookings.show', Booking::first()));
});

// --- Concurrency / overlap --------------------------------------------------

test('an overlapping hold is rejected while another is active', function () {
    // Customer A holds 15:00–17:00.
    $this->actingAs($this->customer)->post(route('bookings.store'), holdPayload());

    // Customer B tries 16:00–17:00 — overlaps A.
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post(route('bookings.store'), holdPayload(['start_time' => '16:00', 'duration' => 1]))
        ->assertSessionHasErrors('start_time');

    expect(Booking::count())->toBe(1);
});

test('two concurrent hold attempts cannot both succeed', function () {
    $service = app(BookingService::class);
    $data = [
        'user_id' => $this->customer->id,
        'court_id' => $this->court->id,
        'booking_date' => $this->date,
        'start_time' => '15:00',
        'end_time' => '17:00',
    ];

    $service->create($data);

    expect(fn () => $service->create([...$data, 'user_id' => User::factory()->create()->id, 'start_time' => '16:00', 'end_time' => '18:00']))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(Booking::count())->toBe(1);
});

// --- Expiry -----------------------------------------------------------------

test('an expired hold releases the slot for availability even before cleanup', function () {
    $expired = Booking::factory()->for($this->court)->create([
        'booking_date' => $this->date,
        'start_time' => '15:00',
        'end_time' => '17:00',
        'status' => BookingStatus::PendingPayment,
        'expires_at' => Carbon::now()->subMinute(),
    ]);

    // The lapsed hold must not reserve the slot.
    $slots = collect(app(CourtAvailabilityService::class)
        ->forDate($this->court, Carbon::parse($this->date))['slots'])->keyBy('start_time');

    expect($slots['15:00']['status'])->toBe(CourtAvailabilityService::STATUS_AVAILABLE)
        ->and($expired->isExpired())->toBeTrue();

    // And a new hold on the same slot can be created.
    $this->actingAs($this->customer)->post(route('bookings.store'), holdPayload());

    expect(Booking::where('status', BookingStatus::PendingPayment)->where('expires_at', '>', Carbon::now())->count())->toBe(1);
});

test('the cleanup command marks lapsed holds as expired', function () {
    $lapsed = Booking::factory()->for($this->court)->create([
        'status' => BookingStatus::PendingPayment,
        'expires_at' => Carbon::now()->subMinute(),
    ]);
    $live = Booking::factory()->for($this->court)->create([
        'status' => BookingStatus::PendingPayment,
        'expires_at' => Carbon::now()->addMinutes(5),
    ]);

    $this->artisan('bookings:expire-holds')->assertSuccessful();

    expect($lapsed->fresh()->status)->toBe(BookingStatus::Expired)
        ->and($live->fresh()->status)->toBe(BookingStatus::PendingPayment);
});

// --- Ownership --------------------------------------------------------------

test('a customer can view their own hold with authoritative expiry', function () {
    $this->actingAs($this->customer)->post(route('bookings.store'), holdPayload());
    $booking = Booking::first();

    $this->actingAs($this->customer)
        ->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/booking-detail')
            ->where('booking.data.id', $booking->id)
            ->where('expired', false)
            ->has('server_now')
        );
});

test('a customer cannot view another customers booking', function () {
    $this->actingAs($this->customer)->post(route('bookings.store'), holdPayload());
    $booking = Booking::first();

    $this->actingAs(User::factory()->create())
        ->get(route('bookings.show', $booking))
        ->assertForbidden();
});

test('a guest cannot create a hold', function () {
    $this->post(route('bookings.store'), holdPayload())
        ->assertRedirect(route('login'));
});

// --- Domain rules -----------------------------------------------------------

test('past dates are rejected', function () {
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), holdPayload(['date' => Carbon::today()->subDay()->toDateString()]))
        ->assertSessionHasErrors('booking_date');
});

test('bookings beyond the maximum duration are rejected', function () {
    app(AppSettings::class)->update(['max_booking_hours' => 2]);

    $this->actingAs($this->customer)
        ->post(route('bookings.store'), holdPayload(['start_time' => '10:00', 'duration' => 4]))
        ->assertSessionHasErrors('end_time');
});

test('blocked hours cannot be held', function () {
    $this->court->blocks()->create([
        'block_date' => $this->date,
        'start_time' => '15:00',
        'end_time' => '16:00',
        'reason' => CourtBlockReason::Maintenance,
        'created_by' => null,
    ]);

    $this->actingAs($this->customer)
        ->post(route('bookings.store'), holdPayload())
        ->assertSessionHasErrors('start_time');
});

test('a closed court cannot be held', function () {
    $this->court->schedules()
        ->where('day_of_week', Carbon::parse($this->date)->dayOfWeek)
        ->update(['is_open' => false, 'opening_time' => null, 'closing_time' => null]);

    $this->actingAs($this->customer)
        ->post(route('bookings.store'), holdPayload())
        ->assertSessionHasErrors('start_time');
});

test('an inactive court cannot be held', function () {
    $inactive = Court::factory()->inactive()->create();

    $this->actingAs($this->customer)
        ->post(route('bookings.store'), holdPayload(['court' => $inactive->slug]))
        ->assertRedirect(route('courts.index'));
});

test('non-whole-hour start times are rejected', function () {
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), holdPayload(['start_time' => '15:30']))
        ->assertSessionHasErrors('start_time');
});
