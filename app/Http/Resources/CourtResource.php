<?php

namespace App\Http\Resources;

use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Court
 */
class CourtResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $opening = substr((string) $this->opening_time, 0, 5);
        $closing = substr((string) $this->closing_time, 0, 5);
        $rate = $this->hourly_rate / 100;

        // Only truthful when the caller eager-loaded the counts; anywhere else
        // the UI must not offer a delete it cannot justify.
        $hasUsageCounts = isset($this->bookings_count, $this->open_play_sessions_count);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'hourly_rate' => round($rate, 2),
            'hourly_rate_formatted' => '₱'.number_format($rate, 2),
            'opening_time' => $opening,
            'closing_time' => $closing,
            'opening_hours' => $opening.' – '.$closing,
            'is_active' => (bool) $this->is_active,
            'can_delete' => $hasUsageCounts
                && (int) $this->bookings_count === 0
                && (int) $this->open_play_sessions_count === 0,
            'image_url' => $this->image_url,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
