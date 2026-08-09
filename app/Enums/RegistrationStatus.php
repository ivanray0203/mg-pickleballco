<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Reserved = 'reserved';
    case Confirmed = 'confirmed';
    case Waitlisted = 'waitlisted';
    case CheckedIn = 'checked_in';
    case Cancelled = 'cancelled';

    /**
     * Get the human-readable label for the registration status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Reserved => 'Reserved',
            self::Confirmed => 'Confirmed',
            self::Waitlisted => 'Waitlisted',
            self::CheckedIn => 'Checked In',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether this registration counts against the session's capacity.
     */
    public function occupiesSlot(): bool
    {
        return in_array($this, [self::Reserved, self::Confirmed, self::CheckedIn], true);
    }

    /**
     * Registration statuses (by value) that count against capacity.
     *
     * @return array<int, string>
     */
    public static function occupyingValues(): array
    {
        return array_values(array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->occupiesSlot()),
        ));
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
