<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    config()->set('services.payment.driver', 'paymongo');
    config()->set('services.paymongo.secret_key', 'sk_test_123');
    config()->set('services.paymongo.webhook_secret', 'whsec_test_123');
    $this->app->forgetInstance(PaymentGateway::class);

    // Stub the hosted checkout creation.
    Http::fake([
        'api.paymongo.com/*' => Http::response([
            'data' => [
                'id' => 'cs_test_abc',
                'attributes' => ['checkout_url' => 'https://checkout.paymongo.test/cs_test_abc'],
            ],
        ], 200),
    ]);

    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create(['hourly_rate' => 40000]);
    $this->booking = Booking::factory()->for($this->court)->for($this->customer)->pending()->create([
        'total_amount' => 80000, // ₱800.00
        'expires_at' => Carbon::now()->addMinutes(10),
    ]);
});

/**
 * Post a genuinely-signed provider webhook for a paid checkout session.
 */
function paidWebhook(Payment $payment): TestResponse
{
    $raw = json_encode([
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
    ]);

    return test()->call(
        'POST',
        route('webhooks.paymongo'),
        [], [], [],
        [
            'HTTP_PAYMONGO_SIGNATURE' => PayMongoGateway::signature($raw, 'whsec_test_123', 1700000000),
            'CONTENT_TYPE' => 'application/json',
        ],
        $raw,
    );
}

// --- Checkout page ----------------------------------------------------------

test('the owner can view the GCash checkout', function () {
    $this->actingAs($this->customer)
        ->get(route('bookings.checkout', $this->booking))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/checkout')
            ->where('booking.data.total_amount_formatted', '₱800.00')
            ->has('server_now')
        );
});

test('a non-owner cannot view the checkout', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('bookings.checkout', $this->booking))
        ->assertForbidden();
});

test('checkout redirects to the hold page when the booking is not payable', function () {
    $this->booking->update(['status' => BookingStatus::Cancelled]);

    $this->actingAs($this->customer)
        ->get(route('bookings.checkout', $this->booking))
        ->assertRedirect(route('bookings.show', $this->booking));
});

test('checkout redirects a confirmed booking to the success page', function () {
    $this->booking->update(['status' => BookingStatus::Confirmed]);

    $this->actingAs($this->customer)
        ->get(route('bookings.checkout', $this->booking))
        ->assertRedirect(route('bookings.payment.success', $this->booking));
});

// --- Pay --------------------------------------------------------------------

test('pay creates a payment attempt with the server-derived amount and redirects to the provider', function () {
    $this->actingAs($this->customer)
        ->post(route('bookings.pay', $this->booking))
        ->assertRedirect('https://checkout.paymongo.test/cs_test_abc');

    $payment = Payment::first();

    expect($payment)->not->toBeNull()
        ->and($payment->booking_id)->toBe($this->booking->id)
        ->and($payment->amount)->toBe(80000) // matches the booking, not the browser
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->provider_reference)->toBe('cs_test_abc');
});

// --- Embedded checkout ------------------------------------------------------

test('the checkout page reports whether the provider checkout is embedded', function () {
    $this->actingAs($this->customer)
        ->get(route('bookings.checkout', $this->booking))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('embed_checkout', false));

    config()->set('services.paymongo.embed_checkout', true);

    $this->actingAs($this->customer)
        ->get(route('bookings.checkout', $this->booking))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('embed_checkout', true));
});

test('an embedded pay request returns the checkout url as json instead of redirecting', function () {
    config()->set('services.paymongo.embed_checkout', true);

    $this->actingAs($this->customer)
        ->postJson(route('bookings.pay', $this->booking), ['embedded' => true])
        ->assertOk()
        ->assertExactJson(['checkout_url' => 'https://checkout.paymongo.test/cs_test_abc']);

    expect(Payment::first()->status)->toBe(PaymentStatus::Pending);
});

test('an embedded pay request on a dead hold reports a conflict rather than a redirect', function () {
    config()->set('services.paymongo.embed_checkout', true);
    $this->booking->update(['expires_at' => Carbon::now()->subMinute()]);

    $this->actingAs($this->customer)
        ->postJson(route('bookings.pay', $this->booking), ['embedded' => true])
        ->assertStatus(409);

    expect(Payment::count())->toBe(0);
});

test('the embedded flag is ignored while embedding is switched off', function () {
    // Guards against a crafted request coaxing the URL out as JSON when the
    // site is still on the redirect flow.
    $this->actingAs($this->customer)
        ->post(route('bookings.pay', $this->booking), ['embedded' => true])
        ->assertRedirect('https://checkout.paymongo.test/cs_test_abc');
});

test('a non-owner cannot pay for a booking', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('bookings.pay', $this->booking))
        ->assertForbidden();

    expect(Payment::count())->toBe(0);
});

test('an expired hold cannot be paid', function () {
    $this->booking->update(['expires_at' => Carbon::now()->subMinute()]);

    $this->actingAs($this->customer)
        ->post(route('bookings.pay', $this->booking))
        ->assertRedirect(route('bookings.show', $this->booking));

    expect(Payment::count())->toBe(0);
});

test('repeated pay clicks reuse the same attempt', function () {
    $this->actingAs($this->customer)->post(route('bookings.pay', $this->booking));
    $this->actingAs($this->customer)->post(route('bookings.pay', $this->booking));

    expect(Payment::count())->toBe(1);
    Http::assertSentCount(1); // only one gateway checkout session created
});

test('a guest cannot pay', function () {
    $this->post(route('bookings.pay', $this->booking))->assertRedirect(route('login'));
});

// --- Success page / webhook authority --------------------------------------

test('reaching the success page never confirms the booking', function () {
    $this->actingAs($this->customer)->post(route('bookings.pay', $this->booking));

    $this->actingAs($this->customer)
        ->get(route('bookings.payment.success', $this->booking))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/payment-success')
            ->where('confirmed', false)
        );

    expect($this->booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});

test('only a verified webhook confirms the booking', function () {
    $this->actingAs($this->customer)->post(route('bookings.pay', $this->booking));
    $payment = Payment::first();

    // Before the webhook: still pending.
    expect($this->booking->fresh()->status)->toBe(BookingStatus::PendingPayment);

    paidWebhook($payment)->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($this->booking->fresh()->status)->toBe(BookingStatus::Confirmed);

    $this->actingAs($this->customer)
        ->get(route('bookings.payment.success', $this->booking))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('confirmed', true));
});

test('a duplicate paid webhook confirms the booking exactly once', function () {
    $this->actingAs($this->customer)->post(route('bookings.pay', $this->booking));
    $payment = Payment::first();

    paidWebhook($payment)->assertOk();
    $confirmedAt = $this->booking->fresh()->confirmed_at;
    paidWebhook($payment)->assertOk();

    expect($this->booking->fresh()->confirmed_at->equalTo($confirmedAt))->toBeTrue();
});

test('an invalid webhook signature does not confirm the booking', function () {
    $this->actingAs($this->customer)->post(route('bookings.pay', $this->booking));
    $payment = Payment::first();

    $raw = json_encode(['data' => ['id' => 'evt_x', 'attributes' => [
        'type' => 'checkout_session.payment.paid',
        'data' => ['id' => $payment->provider_reference, 'attributes' => []],
    ]]]);

    $this->call('POST', route('webhooks.paymongo'), [], [], [], [
        'HTTP_PAYMONGO_SIGNATURE' => 't=1700000000,te=forged',
        'CONTENT_TYPE' => 'application/json',
    ], $raw)->assertStatus(401);

    expect($this->booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});

test('a webhook amount mismatch does not confirm the booking', function () {
    $this->actingAs($this->customer)->post(route('bookings.pay', $this->booking));
    $payment = Payment::first();

    $raw = json_encode(['data' => ['id' => 'evt_y', 'attributes' => [
        'type' => 'checkout_session.payment.paid',
        'data' => ['id' => $payment->provider_reference, 'attributes' => [
            'payments' => [['attributes' => ['amount' => 100]]], // wrong amount
        ]],
    ]]]);

    $this->call('POST', route('webhooks.paymongo'), [], [], [], [
        'HTTP_PAYMONGO_SIGNATURE' => PayMongoGateway::signature($raw, 'whsec_test_123', 1700000000),
        'CONTENT_TYPE' => 'application/json',
    ], $raw)->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($this->booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});

// --- Cancel -----------------------------------------------------------------

test('the cancel page offers a retry while the hold is live', function () {
    $this->actingAs($this->customer)
        ->get(route('bookings.payment.cancel', $this->booking))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/payment-cancelled')
            ->where('can_retry', true)
        );
});
