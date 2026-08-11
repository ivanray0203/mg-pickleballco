<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PayMongoGateway;
use App\Settings\AppSettings;
use Illuminate\Support\Facades\Crypt;
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

test('paymongo credentials are stored encrypted and never sent to the browser', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), settingsPayload([
            'paymongo_public_key' => 'pk_test_public',
            'paymongo_secret_key' => 'sk_test_secret',
            'paymongo_webhook_secret' => 'whsk_test_hook',
        ]))
        ->assertRedirect(route('admin.settings.edit'));

    $stored = Setting::whereKey('paymongo_secret_key')->value('value');

    expect($stored)->not->toBe('sk_test_secret')
        ->and(Crypt::decryptString($stored))->toBe('sk_test_secret');

    $settings = app(AppSettings::class);

    expect($settings->paymongoPublicKey())->toBe('pk_test_public')
        ->and($settings->paymongoSecretKey())->toBe('sk_test_secret')
        ->and($settings->paymongoWebhookSecret())->toBe('whsk_test_hook');

    $this->actingAs($this->admin)
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('settings.paymongo_secret_key')
            ->where('credentials.paymongo_secret_key.source', 'database')
            ->where('credentials.paymongo_secret_key.masked', '••••••••cret')
        );
});

test('a stored credential wins over the environment fallback', function () {
    config()->set('services.paymongo.secret_key', 'sk_from_env');

    expect(app(AppSettings::class)->paymongoSecretKey())->toBe('sk_from_env');

    app(AppSettings::class)->update(['paymongo_secret_key' => 'sk_from_db']);

    expect(app(AppSettings::class)->paymongoSecretKey())->toBe('sk_from_db');
});

test('clearing a credential restores the environment fallback', function () {
    config()->set('services.paymongo.webhook_secret', 'whsk_from_env');

    app(AppSettings::class)->update(['paymongo_webhook_secret' => 'whsk_from_db']);

    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), settingsPayload([
            'paymongo_webhook_secret' => '',
        ]));

    expect(app(AppSettings::class)->paymongoWebhookSecret())->toBe('whsk_from_env');
});

test('omitting a credential leaves the stored value untouched', function () {
    app(AppSettings::class)->update(['paymongo_secret_key' => 'sk_keep_me']);

    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), settingsPayload());

    expect(app(AppSettings::class)->paymongoSecretKey())->toBe('sk_keep_me');
});

test('the payment gateway is built from the stored credentials', function () {
    config()->set('services.paymongo.webhook_secret', 'whsk_from_env');

    app(AppSettings::class)->update(['paymongo_webhook_secret' => 'whsk_from_db']);
    app()->forgetInstance(PaymentGateway::class);

    $payload = '{"data":{"id":"evt_1"}}';
    $gateway = app(PaymentGateway::class);

    expect($gateway->verifyWebhook($payload, PayMongoGateway::signature($payload, 'whsk_from_db', 1700000000)))->toBeTrue()
        ->and($gateway->verifyWebhook($payload, PayMongoGateway::signature($payload, 'whsk_from_env', 1700000000)))->toBeFalse();
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
