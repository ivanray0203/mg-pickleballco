<?php

namespace App\Enums;

enum EquipmentType: string
{
    case Paddle = 'paddle';
    case Ball = 'ball';
    case Shoes = 'shoes';
    case Other = 'other';

    /**
     * Get the human-readable label for the equipment type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Paddle => 'Paddle',
            self::Ball => 'Ball',
            self::Shoes => 'Shoes',
            self::Other => 'Other',
        };
    }

    /**
     * Get the type options formatted for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
