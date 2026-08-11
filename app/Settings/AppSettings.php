<?php

namespace App\Settings;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * The single access layer for application settings. Business/booking/payment
 * options live here (with their defaults) instead of being scattered as magic
 * constants across the codebase.
 *
 * Resolved as a singleton and memoized per request; {@see update()} clears the
 * memo so subsequent reads see fresh values.
 *
 * Payment provider credentials may be set here by an admin. They are encrypted
 * at rest and always fall back to environment configuration
 * (config/services.php) when no value has been stored.
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
        'paymongo_public_key' => null,
        'paymongo_secret_key' => null,
        'paymongo_webhook_secret' => null,
    ];

    /**
     * Credential keys: encrypted at rest, never sent to the browser, and each
     * backed by an environment fallback when unset.
     *
     * @var array<string, string>
     */
    public const SECRET_KEYS = [
        'paymongo_public_key' => 'services.paymongo.public_key',
        'paymongo_secret_key' => 'services.paymongo.secret_key',
        'paymongo_webhook_secret' => 'services.paymongo.webhook_secret',
    ];

    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    /**
     * All settings merged over their defaults, with credentials decrypted.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $stored = Setting::all()->pluck('value', 'key')->all();

        foreach (array_keys(self::SECRET_KEYS) as $key) {
            if (array_key_exists($key, $stored)) {
                $stored[$key] = $this->decrypt($key, $stored[$key]);
            }
        }

        return $this->memo = array_merge(self::DEFAULTS, $stored);
    }

    /**
     * All settings except credentials — the shape safe to send to the browser.
     *
     * @return array<string, mixed>
     */
    public function withoutSecrets(): array
    {
        return array_diff_key($this->all(), self::SECRET_KEYS);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Persist a set of settings. Unknown keys are ignored so callers can't
     * write arbitrary rows (a mass-assignment guard for the settings store).
     *
     * Credentials are encrypted before storage; a blank credential is stored as
     * null, which restores the environment fallback.
     *
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            if (array_key_exists($key, self::SECRET_KEYS)) {
                $value = is_string($value) ? trim($value) : null;
                $value = ($value === null || $value === '') ? null : Crypt::encryptString($value);
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

    public function paymongoPublicKey(): ?string
    {
        return $this->credential('paymongo_public_key');
    }

    public function paymongoSecretKey(): ?string
    {
        return $this->credential('paymongo_secret_key');
    }

    public function paymongoWebhookSecret(): ?string
    {
        return $this->credential('paymongo_webhook_secret');
    }

    /**
     * Where each credential is currently coming from, and a masked preview of
     * it. Safe to send to the browser — the full value never leaves the server.
     *
     * @return array<string, array{configured: bool, source: string|null, masked: string|null}>
     */
    public function credentialStatus(): array
    {
        $status = [];

        foreach (array_keys(self::SECRET_KEYS) as $key) {
            $stored = $this->storedCredential($key);
            $value = $this->credential($key);

            $status[$key] = [
                'configured' => $value !== null,
                'source' => match (true) {
                    $stored !== null => 'database',
                    $value !== null => 'environment',
                    default => null,
                },
                'masked' => $value !== null ? $this->mask($value) : null,
            ];
        }

        return $status;
    }

    /**
     * A stored credential, falling back to its environment configuration.
     */
    private function credential(string $key): ?string
    {
        $stored = $this->storedCredential($key);

        if ($stored !== null) {
            return $stored;
        }

        $fallback = config(self::SECRET_KEYS[$key]);
        $fallback = is_string($fallback) ? trim($fallback) : null;

        return ($fallback === null || $fallback === '') ? null : $fallback;
    }

    /**
     * The credential as stored in the database, ignoring any env fallback.
     */
    private function storedCredential(string $key): ?string
    {
        $stored = $this->get($key);
        $stored = is_string($stored) ? trim($stored) : null;

        return ($stored === null || $stored === '') ? null : $stored;
    }

    /**
     * Decrypt a stored credential. A value that cannot be decrypted (most
     * likely because APP_KEY was rotated) is treated as unset so the
     * environment fallback takes over rather than breaking payments.
     */
    private function decrypt(string $key, mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            Log::warning('Could not decrypt stored credential; falling back to configuration.', [
                'key' => $key,
            ]);

            return null;
        }
    }

    /**
     * Mask a credential down to its last four characters.
     */
    private function mask(string $value): string
    {
        return str_repeat('•', 8).mb_substr($value, -4);
    }
}
