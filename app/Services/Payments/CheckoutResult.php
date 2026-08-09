<?php

namespace App\Services\Payments;

use Illuminate\Support\Carbon;

/**
 * Provider-agnostic result of creating a hosted checkout session.
 */
class CheckoutResult
{
    public function __construct(
        public string $providerReference,
        public string $checkoutUrl,
        public ?Carbon $expiresAt = null,
    ) {}
}
