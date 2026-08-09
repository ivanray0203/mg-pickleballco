<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpenPlayGameSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. Participation is
     * verified in OpenPlayGameSubmissionService.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'team_a' => ['required', 'array', 'min:1', 'max:2'],
            'team_a.*' => ['integer', 'distinct'],
            'team_b' => ['required', 'array', 'min:1', 'max:2'],
            'team_b.*' => ['integer', 'distinct'],
            'team_a_score' => ['required', 'integer', 'min:0', 'max:99'],
            'team_b_score' => ['required', 'integer', 'min:0', 'max:99'],
        ];
    }
}
