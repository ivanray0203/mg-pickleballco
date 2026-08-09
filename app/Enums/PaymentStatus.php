<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Expired => 'Expired',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Whether the payment settled successfully.
     */
    public function isSuccessful(): bool
    {
        return $this === self::Paid;
    }

    /**
     * Whether the payment has reached a terminal state and should no longer
     * transition from a webhook. Used to make webhook processing idempotent.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Refunded], true);
    }

    /**
     * Get the status options formatted for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
