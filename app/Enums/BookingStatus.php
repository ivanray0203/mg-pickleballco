<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Expired = 'expired';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
            self::Expired => 'Expired',
        };
    }

    /**
     * Statuses that occupy a slot and therefore block overlapping bookings.
     *
     * @return array<int, self>
     */
    public static function blocking(): array
    {
        return [self::PendingPayment, self::Confirmed, self::Completed];
    }

    /**
     * Statuses (by value) that occupy a slot, for query constraints.
     *
     * @return array<int, string>
     */
    public static function blockingValues(): array
    {
        return array_map(fn (self $status): string => $status->value, self::blocking());
    }

    /**
     * Whether a booking in this status may still be cancelled.
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::PendingPayment, self::Confirmed], true);
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
