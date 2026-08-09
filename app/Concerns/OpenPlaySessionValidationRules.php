<?php

namespace App\Concerns;

use App\Enums\SkillLevel;
use App\Rules\WholeHour;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait OpenPlaySessionValidationRules
{
    /**
     * Validation rules shared between creating and updating an open-play session.
     *
     * @return array<string, array<int, ValidationRule|Enum|string>>
     */
    protected function openPlaySessionRules(): array
    {
        return [
            'court_id' => ['required', Rule::exists('courts', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'session_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', new WholeHour],
            'end_time' => ['required', new WholeHour, 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:4', 'max:100'],
            'court_count' => ['required', 'integer', 'min:1', 'max:20'],
            'skill_level' => ['nullable', Rule::enum(SkillLevel::class)],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
