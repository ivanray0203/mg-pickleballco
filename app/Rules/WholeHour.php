<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures a time value falls on a whole-hour boundary (e.g. "13:00"),
 * enforcing the core booking rule server-side.
 */
class WholeHour implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^([01]\d|2[0-3]):00$/', $value) !== 1) {
            $fail('The :attribute must be a whole hour (e.g. 13:00).');
        }
    }
}
