<?php

use App\Enums\BookingStatus;
use App\Enums\CourtBlockReason;
use App\Enums\DayOfWeek;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->court = Court::factory()->create(['name' => 'Center Court']);

    foreach (DayOfWeek::cases() as $day) {
        CourtSchedule::factory()->for($this->court)->create([
            'day_of_week' => $day,
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_open' => true,
        ]);
    }
});

test('creating a court is audited', function () {
    $this->actingAs($this->admin)->post(route('admin.courts.store'), [
        'name' => 'New Court',
        'description' => null,
        'type' => 'indoor',
        'hourly_rate' => 30,
        'opening_time' => '08:00',
        'closing_time' => '22:00',
        'is_active' => true,
    ]);

    $log = AuditLog::where('action', 'court.created')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($this->admin->id)
        ->and($log->auditable_type)->toBe(Court::class)
        ->and($log->metadata['name'])->toBe('New Court');
});

test('disabling a court is audited', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.courts.toggle-status', $this->court));

    expect(AuditLog::where('action', 'court.disabled')->exists())->toBeTrue();
});

test('updating a schedule is audited', function () {
    $days = collect(DayOfWeek::cases())->map(fn (DayOfWeek $day): array => [
        'day_of_week' => $day->value,
        'is_open' => true,
        'opening_time' => '08:00',
        'closing_time' => '20:00',
    ])->all();

    $this->actingAs($this->admin)
        ->put(route('admin.courts.schedule.update', $this->court), ['days' => $days]);

    expect(AuditLog::where('action', 'court.schedule_updated')->exists())->toBeTrue();
});

test('blocking a court is audited', function () {
    $this->actingAs($this->admin)->post(route('admin.courts.blocks.store', $this->court), [
        'block_date' => now()->addWeek()->toDateString(),
        'start_time' => '08:00',
        'end_time' => '10:00',
        'reason' => CourtBlockReason::Maintenance->value,
    ]);

    $log = AuditLog::where('action', 'court.blocked')->first();

    expect($log)->not->toBeNull()
        ->and($log->metadata['reason'])->toBe(CourtBlockReason::Maintenance->value);
});

test('cancelling a booking is audited', function () {
    $booking = Booking::factory()->for($this->court)->create(['status' => BookingStatus::Confirmed]);

    $this->actingAs($this->admin)->patch(route('admin.bookings.cancel', $booking), [
        'cancellation_reason' => 'Weather.',
    ]);

    $log = AuditLog::where('action', 'booking.cancelled')->first();

    expect($log)->not->toBeNull()
        ->and($log->auditable_id)->toBe($booking->id)
        ->and($log->metadata['reason'])->toBe('Weather.');
});

test('changing settings is audited without storing raw values', function () {
    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        'business_name' => 'Ace',
        'contact_email' => null,
        'contact_number' => null,
        'address' => null,
        'booking_hold_minutes' => 10,
        'max_booking_hours' => 4,
        'advance_booking_days' => 30,
        'gcash_enabled' => true,
    ]);

    $log = AuditLog::where('action', 'settings.updated')->first();

    // Only the changed keys are recorded — not their values.
    expect($log)->not->toBeNull()
        ->and($log->metadata['keys'])->toContain('business_name')
        ->and($log->auditable_type)->toBeNull();
});
