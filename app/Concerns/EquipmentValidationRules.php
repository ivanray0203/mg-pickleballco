<?php

namespace App\Concerns;

use App\Enums\EquipmentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait EquipmentValidationRules
{
    /**
     * Get the validation rules shared between creating and updating equipment.
     *
     * @return array<string, array<int, ValidationRule|Enum|string>>
     */
    protected function equipmentRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::enum(EquipmentType::class)],
            'hourly_rate' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'total_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
