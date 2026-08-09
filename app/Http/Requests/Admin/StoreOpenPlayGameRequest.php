<?php

namespace App\Http\Requests\Admin;

use App\Models\OpenPlaySession;
use Illuminate\Foundation\Http\FormRequest;

class StoreOpenPlayGameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $session = $this->route('openPlaySession');

        return $session instanceof OpenPlaySession
            && ($this->user()?->can('update', $session) ?? false);
    }

    /**
     * Get the validation rules that apply to the request. Cross-field rules
     * (team parity, ties, session membership) live in OpenPlayGameService so
     * the same guarantees hold wherever a game is recorded.
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
