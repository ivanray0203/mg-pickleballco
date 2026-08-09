<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Booking;
use App\Models\OpenPlayRegistration;
use App\Models\Payment;
use App\Notifications\OpenPlayRegistrationConfirmed;
use App\Services\Payments\PayMongoGateway;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    config()->set('services.payment.driver', 'paymongo');
    config()->set('services.paymongo.secret_key', 'sk_test_123');
    config()->set('services.paymongo.webhook_secret', 'whsec_test_123');
    $this->app->forgetInstance(PaymentGateway::class);
});

/**
 * POST a raw JSON body to the webhook, signing it exactly as PayMongo would.
 */
function postWebhook(array $payload, ?string $signature = null): \Illuminate\Testing\TestResponse
{
    $raw = json_encode($payload);
    $signature ??= PayMongoGateway::signature($raw, 'whsec_test_123', 1700000000);

    return test()->call(
        'POST',
        route('webhooks.paymongo'),
        [], [], [],
        ['HTTP_PAYMONGO_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
        $raw,
    );
}

function paidWebhookPayload(Payment $payment): array
{
    return [
        'data' => [
            'id' => 'evt_'.fake()->uuid(),
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => $payment->provider_reference,
                    'attributes' => [
                        'payments' => [['attributes' => ['amount' => $payment->amount]]],
                    ],
                ],
            ],
        ],
    ];
}

test('a verified successful webhook confirms the booking', function () {
    $booking = Booking::factory()->pending()->create(['total_amount' => 3000]);
    $payment = Payment::factory()->for($booking)->create([
        'amount' => 3000,
        'provider_reference' => 'cs_hook_ok',
    ]);

    postWebhook(paidWebhookPayload($payment))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

test('a webhook with an invalid signature is rejected and changes nothing', function () {
    $booking = Booking::factory()->pending()->create(['total_amount' => 3000]);
    $payment = Payment::factory()->for($booking)->create([
        'amount' => 3000,
        'provider_reference' => 'cs_hook_bad',
    ]);

    postWebhook(paidWebhookPayload($payment), signature: 't=1700000000,te=forged')
        ->assertStatus(401);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});

test('a webhook with a missing signature is rejected', function () {
    postWebhook(['data' => ['id' => 'evt_x']], signature: '')->assertStatus(401);
});

test('duplicate webhook deliveries confirm the booking exactly once', function () {
    $booking = Booking::factory()->pending()->create(['total_amount' => 3000]);
    $payment = Payment::factory()->for($booking)->create([
        'amount' => 3000,
        'provider_reference' => 'cs_hook_dup',
    ]);

    $payload = paidWebhookPayload($payment);

    postWebhook($payload)->assertOk();
    $paidAt = $payment->fresh()->paid_at;
    postWebhook($payload)->assertOk();

    expect($payment->fresh()->paid_at->equalTo($paidAt))->toBeTrue()
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

test('an unhandled event type is acknowledged without side effects', function () {
    postWebhook([
        'data' => ['id' => 'evt_ping', 'attributes' => ['type' => 'source.chargeable', 'data' => []]],
    ])->assertOk();

    expect(Payment::count())->toBe(0);
});

test('a verified successful webhook confirms an open play registration', function () {
    $registration = OpenPlayRegistration::factory()->reserved()->create([
        'expires_at' => now()->addMinutes(15),
    ]);
    $payment = Payment::factory()->create([
        'booking_id' => null,
        'open_play_registration_id' => $registration->id,
        'amount' => 20000,
        'provider_reference' => 'cs_reg_ok',
    ]);

    postWebhook(paidWebhookPayload($payment))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($registration->fresh()->status)->toBe(RegistrationStatus::Confirmed)
        ->and($registration->fresh()->expires_at)->toBeNull();
});

test('a paid open play webhook notifies the registrant', function () {
    Notification::fake();

    $registration = OpenPlayRegistration::factory()->reserved()->create([
        'expires_at' => now()->addMinutes(15),
    ]);
    $payment = Payment::factory()->create([
        'booking_id' => null,
        'open_play_registration_id' => $registration->id,
        'amount' => 20000,
        'provider_reference' => 'cs_reg_notify',
    ]);

    postWebhook(paidWebhookPayload($payment))->assertOk();

    Notification::assertSentTo($registration->user, OpenPlayRegistrationConfirmed::class);
});

test('an expired webhook releases a reserved open play registration', function () {
    $registration = OpenPlayRegistration::factory()->reserved()->create([
        'expires_at' => now()->addMinutes(15),
    ]);
    $payment = Payment::factory()->create([
        'booking_id' => null,
        'open_play_registration_id' => $registration->id,
        'amount' => 20000,
        'provider_reference' => 'cs_reg_expired',
    ]);

    postWebhook([
        'data' => [
            'id' => 'evt_'.fake()->uuid(),
            'attributes' => [
                'type' => 'checkout_session.expired',
                'data' => ['id' => $payment->provider_reference, 'attributes' => []],
            ],
        ],
    ])->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Expired)
        ->and($registration->fresh()->status)->toBe(RegistrationStatus::Cancelled);
});
