<?php

namespace App\Services\Payments;

/**
 * Provider-agnostic, normalized view of a payment webhook event. Concrete
 * gateways translate their raw payload into this shape so the payment domain
 * never depends on a specific provider's JSON structure.
 */
class WebhookEvent
{
    /**
     * @param  'paid'|'failed'|'expired'|'ignored'  $type
     * @param  array<string, mixed>  $metadata  Non-sensitive echo for auditing.
     */
    public function __construct(
        public string $id,
        public string $type,
        public ?string $providerReference,
        public ?int $amount = null,
        public array $metadata = [],
    ) {}

    public function isPaid(): bool
    {
        return $this->type === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->type === 'failed';
    }

    public function isExpired(): bool
    {
        return $this->type === 'expired';
    }
}
