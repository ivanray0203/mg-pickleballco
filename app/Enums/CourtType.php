<?php

namespace App\Enums;

enum CourtType: string
{
    case Indoor = 'indoor';
    case Outdoor = 'outdoor';

    /**
     * Get the human-readable label for the court type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Indoor => 'Indoor',
            self::Outdoor => 'Outdoor',
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
