<?php

namespace App\Enums;

/**
 * Days of the week, using the same numbering as Carbon's `dayOfWeek`
 * (0 = Sunday … 6 = Saturday).
 */
enum DayOfWeek: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    /**
     * Get the human-readable label for the day.
     */
    public function label(): string
    {
        return ucfirst($this->name);
    }

    /**
     * The days of the week in display order (Monday first).
     *
     * @return array<int, self>
     */
    public static function ordered(): array
    {
        return [
            self::Monday,
            self::Tuesday,
            self::Wednesday,
            self::Thursday,
            self::Friday,
            self::Saturday,
            self::Sunday,
        ];
    }
}
