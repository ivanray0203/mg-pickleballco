<?php

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Models\Payment;

/**
 * Contract every payment provider must satisfy. Booking and payment domain
 * code depends only on this interface, never on a concrete gateway SDK, so a
 * provider can be swapped via configuration without touching business logic.
 */
interface PaymentGateway
{
    /**
     * The provider this gateway represents.
     */
    public function provider(): PaymentProvider;

    /**
     * Create a hosted checkout session for the given (pending) payment and
     * return the URL the customer should be redirected to.
     */
    public function createCheckout(Payment $payment): CheckoutResult;

    /**
     * Verify that a raw webhook request genuinely originated from the provider
     * using the signature header. Must be constant-time and never trust the
     * payload before this returns true.
     */
    public function verifyWebhook(string $payload, string $signatureHeader): bool;

    /**
     * Translate an already-verified raw webhook payload into a normalized
     * event, or null if the event is not one we act on.
     *
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload): ?WebhookEvent;

    /**
     * Fetch the current state of a payment directly from the provider. Used to
     * reconcile status independently of webhooks.
     *
     * @return array<string, mixed>
     */
    public function retrievePayment(string $providerReference): array;
}
