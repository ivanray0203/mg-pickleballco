<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Deliberately omits raw provider metadata, checkout URLs and any secret
     * material — the admin panel only needs a safe, auditable summary.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booking = $this->whenLoaded('booking');

        return [
            'id' => $this->id,
            'payment_reference' => $this->payment_reference,
            'provider' => $this->provider->value,
            'provider_label' => $this->provider->label(),
            'provider_reference' => $this->provider_reference,
            'payment_method' => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'amount' => round($this->amount / 100, 2),
            'amount_formatted' => '₱'.number_format($this->amount / 100, 2),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'booking' => $this->when((bool) $booking, fn (): array => [
                'id' => $this->booking->id,
                'reference' => $this->booking->booking_reference,
                'date' => $this->booking->booking_date->toDateString(),
                'start_time' => substr($this->booking->start_time, 0, 5),
                'end_time' => substr($this->booking->end_time, 0, 5),
                'court' => $this->booking->court->name,
                'customer' => [
                    'name' => $this->booking->user->name,
                    'email' => $this->booking->user->email,
                ],
            ]),
        ];
    }
}
