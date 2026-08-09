<?php

namespace App\Http\Requests\Admin;

use App\Concerns\CourtValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourtRequest extends FormRequest
{
    use CourtValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('court')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->courtRules();
    }
}
