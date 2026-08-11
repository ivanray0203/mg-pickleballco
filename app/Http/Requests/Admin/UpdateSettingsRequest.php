<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * PayMongo credentials use `sometimes`: a key that is absent leaves the
     * stored value untouched (so the browser never has to echo a secret back),
     * while an explicitly empty key clears it and restores the .env fallback.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],

            'booking_hold_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'max_booking_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'advance_booking_days' => ['required', 'integer', 'min:1', 'max:365'],

            'gcash_enabled' => ['required', 'boolean'],

            'paymongo_public_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paymongo_secret_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paymongo_webhook_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
