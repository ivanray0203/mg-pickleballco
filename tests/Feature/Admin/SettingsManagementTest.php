<?php

use App\Models\Setting;
use App\Models\User;
use App\Settings\AppSettings;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
});

/**
 * @return array<string, mixed>
 */
function settingsPayload(array $overrides = []): array
{
    return array_merge([
        'business_name' => 'Ace Pickleball',
        'contact_email' => 'hello@ace.test',
        'contact_number' => '+63 900 000 0000',
        'address' => '123 Court St.',
        'booking_hold_minutes' => 15,
        'max_booking_hours' => 3,
        'advance_booking_days' => 45,
        'gcash_enabled' => true,
    ], $overrides);
}

test('admin can view settings with defaults', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/settings')
            ->where('settings.business_name', 'Pickleball Club')
            ->where('settings.booking_hold_minutes', 10)
            ->where('settings.gcash_enabled', true)
            ->has('auditLogs.data')
        );
});

test('admin can update settings and values persist through the access layer', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), settingsPayload())
        ->assertRedirect(route('admin.settings.edit'));

    $settings = app(AppSettings::class);

    expect($settings->businessName())->toBe('Ace Pickleball')
        ->and($settings->bookingHoldMinutes())->toBe(15)
        ->and($settings->maxBookingHours())->toBe(3)
        ->and($settings->advanceBookingDays())->toBe(45)
        ->and($settings->gcashEnabled())->toBeTrue();
});

test('settings updates are validated', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), settingsPayload([
            'business_name' => '',
            'booking_hold_minutes' => 0,
            'max_booking_hours' => 99,
            'advance_booking_days' => -1,
            'contact_email' => 'not-an-email',
        ]))
        ->assertSessionHasErrors([
            'business_name',
            'booking_hold_minutes',
            'max_booking_hours',
            'advance_booking_days',
            'contact_email',
        ]);
});

test('unknown setting keys are ignored by the access layer', function () {
    app(AppSettings::class)->update([
        'business_name' => 'Legit',
        'evil_key' => 'nope',
    ]);

    expect(Setting::whereKey('evil_key')->exists())->toBeFalse()
        ->and(Setting::whereKey('business_name')->exists())->toBeTrue();
});

test('gcash can be disabled', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), settingsPayload(['gcash_enabled' => false]));

    expect(app(AppSettings::class)->gcashEnabled())->toBeFalse();
});

test('customers cannot view or update settings', function () {
    $this->actingAs($this->customer)
        ->get(route('admin.settings.edit'))
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->put(route('admin.settings.update'), settingsPayload())
        ->assertForbidden();
});

test('guests are redirected from settings', function () {
    $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
});
