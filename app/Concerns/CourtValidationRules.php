<?php

namespace App\Concerns;

use App\Enums\CourtType;
use App\Rules\WholeHour;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait CourtValidationRules
{
    /**
     * Get the validation rules shared between creating and updating a court.
     *
     * @return array<string, array<int, ValidationRule|Enum|string>>
     */
    protected function courtRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::enum(CourtType::class)],
            'hourly_rate' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
