<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * These cover shape/format only; the authoritative availability, overlap,
     * pricing and limit checks live in the booking domain.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'court' => ['required', 'string', 'exists:courts,slug'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'regex:/^([01]\d|2[0-3]):00$/'],
            'duration' => ['required', 'integer', 'min:1', 'max:24'],
            // Optional equipment rentals. Prices and stock are re-validated
            // server-side; only shape is checked here.
            'equipment' => ['nullable', 'array', 'max:50'],
            'equipment.*.id' => ['required', 'integer', 'exists:equipment,id'],
            'equipment.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
