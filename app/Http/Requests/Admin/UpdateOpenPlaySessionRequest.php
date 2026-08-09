<?php

namespace App\Http\Requests\Admin;

use App\Concerns\OpenPlaySessionValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOpenPlaySessionRequest extends FormRequest
{
    use OpenPlaySessionValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('openPlaySession')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->openPlaySessionRules();
    }
}
