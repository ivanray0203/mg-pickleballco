<?php

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->court = Court::factory()->create();
    $this->booking = Booking::factory()->for($this->customer)->for($this->court)->create();
});

test('a customer sees only their own payment history', function () {
    Payment::factory()->for($this->booking)->paid()->create();

    $otherBooking = Booking::factory()->for(User::factory())->for($this->court)->create();
    Payment::factory()->for($otherBooking)->paid()->create(['payment_reference' => 'PAY-OTHER-0001']);

    $response = $this->actingAs($this->customer)->get(route('payments.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('client/payments')
        ->has('payments.data', 1)
    );

    expect($response->content())->not->toContain('PAY-OTHER-0001');
});

test('the payment history paginates', function () {
    Payment::factory()->count(13)->for($this->booking)->create();

    $this->actingAs($this->customer)
        ->get(route('payments.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('payments.data', 10)
            ->where('payments.meta.total', 13)
        );
});

test('a customer can view their own payment details', function () {
    $payment = Payment::factory()->for($this->booking)->paid()->create(['amount' => 80000]);

    $this->actingAs($this->customer)
        ->get(route('payments.show', $payment))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('client/payment-detail')
            ->where('payment.data.amount_formatted', '₱800.00')
            ->where('payment.data.status', PaymentStatus::Paid->value)
        );
});

test('a customer cannot view another customers payment', function () {
    $otherBooking = Booking::factory()->for(User::factory())->for($this->court)->create();
    $payment = Payment::factory()->for($otherBooking)->paid()->create();

    $this->actingAs($this->customer)
        ->get(route('payments.show', $payment))
        ->assertForbidden();
});

test('payment details never expose raw provider metadata', function () {
    $payment = Payment::factory()->for($this->booking)->paid()->create([
        'metadata' => ['secret_token' => 'must-not-leak'],
    ]);

    $response = $this->actingAs($this->customer)->get(route('payments.show', $payment));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->missing('payment.data.metadata')
        ->missing('payment.data.checkout_url')
    );

    expect($response->content())->not->toContain('must-not-leak');
});

test('a guest is redirected from payment history', function () {
    $this->get(route('payments.index'))->assertRedirect(route('login'));
});
