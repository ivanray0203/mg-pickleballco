<?php

namespace App\Enums;

enum OpenPlaySessionStatus: string
{
    case Scheduled = 'scheduled';
    case Open = 'open';
    case Full = 'full';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get the human-readable label for the session status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Open => 'Open for Registration',
            self::Full => 'Full',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether the session still occupies its court window and therefore blocks
     * overlapping bookings or other sessions.
     */
    public function blocksSlot(): bool
    {
        return $this !== self::Cancelled;
    }

    /**
     * Statuses (by value) that occupy a court window, for query constraints.
     *
     * @return array<int, string>
     */
    public static function blockingValues(): array
    {
        return array_values(array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->blocksSlot()),
        ));
    }

    /**
     * Whether players may still register for a session in this status.
     */
    public function acceptsRegistrations(): bool
    {
        return in_array($this, [self::Scheduled, self::Open], true);
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
