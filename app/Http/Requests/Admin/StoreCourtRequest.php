<?php

namespace App\Http\Requests\Admin;

use App\Concerns\CourtValidationRules;
use App\Models\Court;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourtRequest extends FormRequest
{
    use CourtValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Court::class) ?? false;
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
