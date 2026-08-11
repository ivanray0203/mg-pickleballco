<?php

namespace App\Settings;

use App\Models\Setting;

/**
 * The single access layer for application settings. Business/booking/payment
 * options live here (with their defaults) instead of being scattered as magic
 * constants across the codebase.
 *
 * Resolved as a singleton and memoized per request; {@see update()} clears the
 * memo so subsequent reads see fresh values.
 *
 * Payment provider secrets are intentionally NOT stored here — those remain in
 * environment/server configuration (config/services.php).
 */
class AppSettings
{
    /**
     * Default values and the authoritative whitelist of settable keys.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'business_name' => 'Pickleball Club',
        'contact_email' => null,
        'contact_number' => null,
        'address' => null,
        'booking_hold_minutes' => 10,
        'max_booking_hours' => 4,
        'advance_booking_days' => 30,
        'gcash_enabled' => true,
    ];

    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    /**
     * All settings merged over their defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->memo ??= array_merge(
            self::DEFAULTS,
            Setting::all()->pluck('value', 'key')->all(),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Persist a set of settings. Unknown keys are ignored so callers can't
     * write arbitrary rows (a mass-assignment guard for the settings store).
     *
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->memo = null;
    }

    public function businessName(): string
    {
        return (string) $this->get('business_name');
    }

    public function contactEmail(): ?string
    {
        $email = $this->get('contact_email');

        return $email !== null ? (string) $email : null;
    }

    public function bookingHoldMinutes(): int
    {
        return (int) $this->get('booking_hold_minutes');
    }

    public function maxBookingHours(): int
    {
        return (int) $this->get('max_booking_hours');
    }

    public function advanceBookingDays(): int
    {
        return (int) $this->get('advance_booking_days');
    }

    public function gcashEnabled(): bool
    {
        return (bool) $this->get('gcash_enabled');
    }
}
