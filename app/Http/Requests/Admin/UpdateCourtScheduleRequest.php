<?php

namespace App\Http\Requests\Admin;

use App\Rules\WholeHour;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourtScheduleRequest extends FormRequest
{
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
        return [
            'days' => ['required', 'array', 'size:7'],
            'days.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'days.*.is_open' => ['required', 'boolean'],
            'days.*.opening_time' => [
                'nullable',
                'required_if:days.*.is_open,true,1',
                'date_format:H:i',
                new WholeHour,
            ],
            'days.*.closing_time' => [
                'nullable',
                'required_if:days.*.is_open,true,1',
                'date_format:H:i',
                new WholeHour,
                'after:days.*.opening_time',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'days.*.closing_time.after' => 'The closing time must be after the opening time.',
            'days.*.opening_time.required_if' => 'An opening time is required for open days.',
            'days.*.closing_time.required_if' => 'A closing time is required for open days.',
        ];
    }
}
