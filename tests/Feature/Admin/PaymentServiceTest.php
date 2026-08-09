<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payments\PayMongoGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.payment.driver', 'paymongo');
    config()->set('services.paymongo.secret_key', 'sk_test_123');
    config()->set('services.paymongo.webhook_secret', 'whsec_test_123');
    $this->app->forgetInstance(PaymentGateway::class);

    $this->service = app(PaymentService::class);
});

/**
 * Build a PayMongo-shaped webhook payload and its normalized event.
 *
 * @return array<string, mixed>
 */
function paidPayload(Payment $payment, ?int $amount = null): array
{
    return [
        'data' => [
            'id' => 'evt_'.fake()->uuid(),
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => [
                    'id' => $payment->provider_reference,
                    'attributes' => [
                        'payments' => [
                            ['attributes' => ['amount' => $amount ?? $payment->amount]],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

test('a booking has many payments and keeps attempt history', function () {
    $booking = Booking::factory()->create();
    Payment::factory()->for($booking)->failed()->create();
    Payment::factory()->for($booking)->paid()->create();

    expect($booking->payments)->toHaveCount(2)
        ->and($booking->latestPayment->isSuccessful())->toBeTrue()
        ->and($booking->payments->first()->booking->is($booking))->toBeTrue();
});

test('initiate creates a pending payment with a provider checkout url', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_test_abc',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.test/cs_test_abc'],
            ],
        ], 200),
    ]);

    $booking = Booking::factory()->pending()->create(['total_amount' => 6000]);

    $payment = $this->service->initiate($booking->fresh());

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount)->toBe(6000)
        ->and($payment->provider_reference)->toBe('cs_test_abc')
        ->and($payment->checkout_url)->toBe('https://checkout.paymongo.test/cs_test_abc');
});

test('initiate snapshots the amount and never confirms the booking itself', function () {
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => ['id' => 'cs_x', 'attributes' => ['checkout_url' => 'https://checkout.test/x']],
        ], 200),
    ]);

    $booking = Booking::factory()->pending()->create(['total_amount' => 4500]);

    $payment = $this->service->initiate($booking->fresh());

    expect($payment->amount)->toBe(4500)
        ->and($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});

test('a successful webhook marks the payment paid and confirms the booking', function () {
    $booking = Booking::factory()->pending()->create(['total_amount' => 3000]);
    $payment = Payment::factory()->for($booking)->create([
        'amount' => 3000,
        'provider_reference' => 'cs_paid_1',
    ]);

    $gateway = app(PaymentGateway::class);
    $event = $gateway->parseWebhook(paidPayload($payment));

    $this->service->processWebhook($event);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->paid_at)->not->toBeNull()
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->fresh()->confirmed_at)->not->toBeNull();
});

test('a failed webhook marks the payment failed and does not confirm the booking', function () {
    $booking = Booking::factory()->pending()->create();
    $payment = Payment::factory()->for($booking)->create(['provider_reference' => 'cs_fail_1']);

    $payload = [
        'data' => [
            'id' => 'evt_fail',
            'attributes' => [
                'type' => 'payment.failed',
                'data' => ['id' => 'cs_fail_1', 'attributes' => []],
            ],
        ],
    ];

    $this->service->processWebhook(app(PaymentGateway::class)->parseWebhook($payload));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($payment->fresh()->failed_at)->not->toBeNull()
        ->and($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});

test('duplicate successful webhooks are idempotent', function () {
    $booking = Booking::factory()->pending()->create(['total_amount' => 3000]);
    $payment = Payment::factory()->for($booking)->create([
        'amount' => 3000,
        'provider_reference' => 'cs_dup_1',
    ]);

    $event = app(PaymentGateway::class)->parseWebhook(paidPayload($payment));

    $this->service->processWebhook($event);
    $firstPaidAt = $payment->fresh()->paid_at;

    // A second (duplicate) delivery must not re-process or change state.
    $this->service->processWebhook($event);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->paid_at->equalTo($firstPaidAt))->toBeTrue()
        ->and(Payment::where('provider_reference', 'cs_dup_1')->count())->toBe(1)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

test('a webhook with a mismatched amount does not confirm the booking', function () {
    $booking = Booking::factory()->pending()->create(['total_amount' => 3000]);
    $payment = Payment::factory()->for($booking)->create([
        'amount' => 3000,
        'provider_reference' => 'cs_amt_1',
    ]);

    // Provider reports a different amount than the snapshot.
    $event = app(PaymentGateway::class)->parseWebhook(paidPayload($payment, amount: 100));

    $this->service->processWebhook($event);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});

test('a webhook for an unknown reference is ignored safely', function () {
    $payload = [
        'data' => [
            'id' => 'evt_unknown',
            'attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => ['id' => 'cs_does_not_exist', 'attributes' => []],
            ],
        ],
    ];

    $this->service->processWebhook(app(PaymentGateway::class)->parseWebhook($payload));

    expect(Payment::count())->toBe(0);
});

test('the gateway verifies a genuine signature and rejects a forged one', function () {
    $gateway = new PayMongoGateway(['webhook_secret' => 'whsec_test_123']);
    $payload = '{"data":{"id":"evt_1"}}';
    $header = PayMongoGateway::signature($payload, 'whsec_test_123', 1700000000);

    expect($gateway->verifyWebhook($payload, $header))->toBeTrue()
        ->and($gateway->verifyWebhook($payload, 't=1700000000,te=deadbeef'))->toBeFalse()
        ->and($gateway->verifyWebhook('{"tampered":true}', $header))->toBeFalse();
});

test('payment references are unique and well-formatted', function () {
    $references = collect(range(1, 25))->map(fn () => $this->service->generateReference());

    expect($references->unique())->toHaveCount(25)
        ->and($references->first())->toMatch('/^PAY-\d{8}-[A-Z0-9]{4}$/');
});
