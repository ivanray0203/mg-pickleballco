<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'booking_date' => $this->booking_date->toDateString(),
            'start_time' => substr($this->start_time, 0, 5),
            'end_time' => substr($this->end_time, 0, 5),
            'duration_hours' => $this->duration_hours,
            'hourly_rate' => round($this->hourly_rate / 100, 2),
            'hourly_rate_formatted' => '₱'.number_format($this->hourly_rate / 100, 2),
            'total_amount' => round($this->total_amount / 100, 2),
            'total_amount_formatted' => '₱'.number_format($this->total_amount / 100, 2),
            // Equipment rental lines, when the relation is loaded.
            'equipment' => $this->relationLoaded('equipmentItems')
                ? $this->equipmentItems->map(fn ($item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_hourly_rate' => round($item->unit_hourly_rate / 100, 2),
                    'unit_hourly_rate_formatted' => '₱'.number_format($item->unit_hourly_rate / 100, 2),
                    'line_total' => round($item->line_total / 100, 2),
                    'line_total_formatted' => '₱'.number_format($item->line_total / 100, 2),
                ])->all()
                : [],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_cancellable' => $this->isCancellable(),
            // Latest payment status, when the relation is loaded (null otherwise).
            'payment_status' => $this->relationLoaded('latestPayment')
                ? $this->latestPayment?->status->value
                : null,
            'payment_status_label' => $this->relationLoaded('latestPayment')
                ? $this->latestPayment?->status->label()
                : null,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'customer' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'court' => [
                'id' => $this->court->id,
                'name' => $this->court->name,
                'slug' => $this->court->slug,
                'type_label' => $this->court->type->label(),
                'image_url' => $this->court->image_url,
            ],
        ];
    }
}
