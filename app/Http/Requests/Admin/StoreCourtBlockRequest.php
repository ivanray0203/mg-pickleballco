<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourtBlockReason;
use App\Models\Court;
use App\Rules\WholeHour;
use App\Services\CourtAvailabilityService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreCourtBlockRequest extends FormRequest
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
            'block_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', new WholeHour],
            'end_time' => ['required', 'date_format:H:i', new WholeHour, 'after:start_time'],
            'reason' => ['required', Rule::enum(CourtBlockReason::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Ensure the block falls within the court's operating hours for the date.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $court = $this->route('court');

            if (! $court instanceof Court) {
                return;
            }

            $withinHours = app(CourtAvailabilityService::class)->isWithinOperatingHours(
                $court,
                Carbon::parse($this->string('block_date')->toString()),
                $this->string('start_time')->toString(),
                $this->string('end_time')->toString(),
            );

            if (! $withinHours) {
                $validator->errors()->add(
                    'start_time',
                    "The block must fall within the court's operating hours for that day.",
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_time.after' => 'The end time must be after the start time.',
        ];
    }
}
