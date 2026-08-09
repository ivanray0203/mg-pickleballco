<?php

namespace App\Enums;

enum CourtBlockReason: string
{
    case Maintenance = 'maintenance';
    case PrivateEvent = 'private_event';
    case Cleaning = 'cleaning';
    case Tournament = 'tournament';
    case Other = 'other';

    /**
     * Get the human-readable label for the reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::Maintenance => 'Court Maintenance',
            self::PrivateEvent => 'Private Event',
            self::Cleaning => 'Cleaning',
            self::Tournament => 'Tournament',
            self::Other => 'Other',
        };
    }

    /**
     * Get the reason options formatted for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $reason): array => ['value' => $reason->value, 'label' => $reason->label()],
            self::cases(),
        );
    }
}
