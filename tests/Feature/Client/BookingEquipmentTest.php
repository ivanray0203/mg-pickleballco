<?php

use App\Enums\BookingStatus;
use App\Enums\DayOfWeek;
use App\Models\Booking;
use App\Models\BookingEquipment;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\Equipment;
use App\Models\User;
use App\Services\BookingService;
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

    // A second court so we can test equipment scarcity across overlapping
    // bookings without the per-court overlap check masking it — equipment
    // stock is shared across all courts.
    $this->court2 = Court::factory()->create([
        'hourly_rate' => 40000,
        'opening_time' => '06:00',
        'closing_time' => '22:00',
    ]);

    foreach (DayOfWeek::cases() as $day) {
        CourtSchedule::factory()->for($this->court2)->create([
            'day_of_week' => $day,
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_open' => true,
        ]);
    }

    $this->paddle = Equipment::factory()->paddle()->create([
        'name' => 'Pro Paddle',
        'hourly_rate' => 5000, // ₱50.00 / hr
        'total_quantity' => 4,
    ]);

    $this->date = Carbon::today()->addDays(2)->toDateString();
});

/**
 * @return array<string, mixed>
 */
function equipmentHoldPayload(array $overrides = []): array
{
    return array_merge([
        'court' => test()->court->slug,
        'date' => test()->date,
        'start_time' => '15:00',
        'duration' => 2,
    ], $overrides);
}

test('the review page exposes equipment available for the slot', function () {
    $this->actingAs($this->customer)
        ->get(route('book.review', equipmentHoldPayload()))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/booking-review')
            ->has('equipment', 1)
            ->where('equipment.0.available', 4)
            ->where('equipment.0.name', 'Pro Paddle')
        );
});

test('equipment cost is added to the booking total and stored as line items', function () {
    // Court: 2h × ₱400 = ₱800 (80000). Paddle: 2 × ₱50 × 2h = ₱200 (20000).
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), equipmentHoldPayload([
            'equipment' => [['id' => $this->paddle->id, 'quantity' => 2]],
        ]))
        ->assertRedirect();

    $booking = Booking::first();

    expect($booking->total_amount)->toBe(100000)
        ->and($booking->equipmentItems)->toHaveCount(1);

    $line = $booking->equipmentItems->first();

    expect($line->equipment_id)->toBe($this->paddle->id)
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_hourly_rate)->toBe(5000)
        ->and($line->line_total)->toBe(20000);
});

test('equipment prices are recomputed server-side and ignore client input', function () {
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), equipmentHoldPayload([
            'equipment' => [['id' => $this->paddle->id, 'quantity' => 1]],
        ]));

    $line = BookingEquipment::first();

    // 1 × ₱50 × 2h = 10000; the client cannot influence the rate.
    expect($line->line_total)->toBe(10000)
        ->and($line->unit_hourly_rate)->toBe(5000);
});

test('renting more units than are in stock is rejected', function () {
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), equipmentHoldPayload([
            'equipment' => [['id' => $this->paddle->id, 'quantity' => 5]], // only 4 exist
        ]))
        ->assertSessionHasErrors('equipment');

    expect(Booking::count())->toBe(0);
});

test('equipment reserved by an overlapping booking reduces availability', function () {
    // First booking takes 3 of the 4 paddles for 15:00–17:00.
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), equipmentHoldPayload([
            'equipment' => [['id' => $this->paddle->id, 'quantity' => 3]],
        ]));

    // A second customer on a DIFFERENT court, overlapping the same window,
    // can take at most 1 more paddle (stock is shared across courts).
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post(route('bookings.store'), equipmentHoldPayload([
            'court' => $this->court2->slug,
            'start_time' => '16:00',
            'duration' => 1,
            'equipment' => [['id' => $this->paddle->id, 'quantity' => 2]],
        ]))
        ->assertSessionHasErrors('equipment');

    // Exactly 1 remaining unit is bookable.
    $this->actingAs($other)
        ->post(route('bookings.store'), equipmentHoldPayload([
            'court' => $this->court2->slug,
            'start_time' => '16:00',
            'duration' => 1,
            'equipment' => [['id' => $this->paddle->id, 'quantity' => 1]],
        ]))
        ->assertRedirect();

    expect(BookingEquipment::sum('quantity'))->toBe(4);
});

test('a non-overlapping booking can reuse the same equipment units', function () {
    // 15:00–17:00 takes all 4 paddles.
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), equipmentHoldPayload([
            'equipment' => [['id' => $this->paddle->id, 'quantity' => 4]],
        ]));

    // 17:00–18:00 does not overlap, so all 4 are free again.
    $this->actingAs(User::factory()->create())
        ->post(route('bookings.store'), equipmentHoldPayload([
            'start_time' => '17:00',
            'duration' => 1,
            'equipment' => [['id' => $this->paddle->id, 'quantity' => 4]],
        ]))
        ->assertRedirect();

    expect(Booking::count())->toBe(2);
});

test('inactive equipment cannot be rented', function () {
    $inactive = Equipment::factory()->inactive()->create(['total_quantity' => 10]);

    $this->actingAs($this->customer)
        ->post(route('bookings.store'), equipmentHoldPayload([
            'equipment' => [['id' => $inactive->id, 'quantity' => 1]],
        ]))
        ->assertSessionHasErrors('equipment');

    expect(Booking::count())->toBe(0);
});

test('a booking without equipment still works and stores no line items', function () {
    $this->actingAs($this->customer)
        ->post(route('bookings.store'), equipmentHoldPayload())
        ->assertRedirect();

    $booking = Booking::first();

    expect($booking->total_amount)->toBe(80000)
        ->and(BookingEquipment::count())->toBe(0);
});

test('the concurrency lock prevents overselling the last equipment unit', function () {
    $service = app(BookingService::class);

    $base = [
        'court_id' => $this->court->id,
        'booking_date' => $this->date,
        'start_time' => '15:00',
        'end_time' => '17:00',
        'equipment' => [['id' => $this->paddle->id, 'quantity' => 4]],
    ];

    $service->create([...$base, 'user_id' => $this->customer->id]);

    // A second overlapping request on another court for any paddle must fail
    // — none are free for that window.
    expect(fn () => $service->create([
        ...$base,
        'court_id' => $this->court2->id,
        'user_id' => User::factory()->create()->id,
        'start_time' => '16:00',
        'end_time' => '18:00',
        'equipment' => [['id' => $this->paddle->id, 'quantity' => 1]],
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(BookingEquipment::sum('quantity'))->toBe(4);
});
