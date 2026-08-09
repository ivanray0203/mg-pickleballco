<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Enums\SkillLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Normalize an empty skill level to null so it clears cleanly.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('skill_level') === '') {
            $this->merge(['skill_level' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()->id),
            'skill_level' => ['nullable', Rule::enum(SkillLevel::class)],
        ];
    }
}
