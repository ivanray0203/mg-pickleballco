<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case GCash = 'gcash';
    case Cash = 'cash';

    /**
     * Get the human-readable label for the method.
     */
    public function label(): string
    {
        return match ($this) {
            self::GCash => 'GCash',
            self::Cash => 'Cash',
        };
    }

    /**
     * Get the method options formatted for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $method): array => ['value' => $method->value, 'label' => $method->label()],
            self::cases(),
        );
    }
}
