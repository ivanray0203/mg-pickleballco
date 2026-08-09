<?php

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
});

test('admin can view the payment list', function () {
    Payment::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/payments/index')
            ->has('payments.data', 3)
            ->has('statuses')
            ->has('methods')
        );
});

test('the payment list paginates', function () {
    Payment::factory()->count(18)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('payments.data', 15)
            ->where('payments.meta.total', 18)
        );
});

test('payments can be filtered by status', function () {
    Payment::factory()->paid()->create();
    Payment::factory()->failed()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index', ['status' => PaymentStatus::Paid->value]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('payments.data', 1)
            ->where('payments.data.0.status', PaymentStatus::Paid->value)
        );
});

test('payments can be searched by customer name', function () {
    $alice = User::factory()->create(['name' => 'Alice Angeles']);
    $booking = Booking::factory()->for($alice)->create();
    Payment::factory()->for($booking)->create();
    Payment::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index', ['search' => 'Alice']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('payments.data', 1));
});

test('admin can view payment details without exposing raw provider data', function () {
    $payment = Payment::factory()->paid()->create([
        'metadata' => ['secret_token' => 'should-not-leak'],
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.show', $payment))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/payments/show')
            ->where('payment.data.payment_reference', $payment->payment_reference)
            ->missing('payment.data.metadata')
            ->missing('payment.data.checkout_url')
        );
});

test('customers cannot access payment management', function () {
    $payment = Payment::factory()->create();

    $this->actingAs($this->customer)
        ->get(route('admin.payments.index'))
        ->assertForbidden();

    $this->actingAs($this->customer)
        ->get(route('admin.payments.show', $payment))
        ->assertForbidden();
});

test('guests are redirected from payment management', function () {
    $this->get(route('admin.payments.index'))->assertRedirect(route('login'));
});
