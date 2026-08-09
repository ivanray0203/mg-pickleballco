<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case PayMongo = 'paymongo';
    case Counter = 'counter';

    /**
     * Get the human-readable label for the provider.
     */
    public function label(): string
    {
        return match ($this) {
            self::PayMongo => 'PayMongo',
            self::Counter => 'Counter',
        };
    }
}
