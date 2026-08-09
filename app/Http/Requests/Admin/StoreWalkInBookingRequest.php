<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Settings\AppSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalkInBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Booking::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(AppSettings $settings): array
    {
        $today = now()->startOfDay()->toDateString();
        $maxDate = now()->startOfDay()->addDays($settings->advanceBookingDays())->toDateString();

        return [
            'customer.mode' => ['required', Rule::in(['existing', 'guest'])],
            'customer.user_id' => [
                'nullable',
                'required_if:customer.mode,existing',
                Rule::exists('users', 'id'),
            ],
            'customer.name' => ['nullable', 'required_if:customer.mode,guest', 'string', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:30'],
            'customer.email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],

            'court_id' => ['required', Rule::exists('courts', 'id')],
            'booking_date' => ['required', 'date_format:Y-m-d', "after_or_equal:{$today}", "before_or_equal:{$maxDate}"],
            'start_time' => ['required', 'regex:/^([01]\d|2[0-3]):00$/'],
            'duration' => ['required', 'integer', 'min:1', 'max:'.$settings->maxBookingHours()],

            'equipment' => ['nullable', 'array'],
            'equipment.*.id' => ['required_with:equipment', 'integer', Rule::exists('equipment', 'id')],
            'equipment.*.quantity' => ['required_with:equipment', 'integer', 'min:1'],

            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer.user_id.required_if' => 'Please select an existing customer.',
            'customer.name.required_if' => 'Please enter the customer name.',
        ];
    }
}
