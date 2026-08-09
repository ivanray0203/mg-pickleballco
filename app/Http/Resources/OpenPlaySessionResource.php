<?php

namespace App\Http\Resources;

use App\Models\OpenPlaySession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OpenPlaySession
 */
class OpenPlaySessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $start = substr((string) $this->start_time, 0, 5);
        $end = substr((string) $this->end_time, 0, 5);
        $price = $this->price_amount / 100;
        $occupied = $this->occupiedSlots();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'court' => [
                'id' => $this->court->id,
                'name' => $this->court->name,
            ],
            'court_id' => $this->court_id,
            'session_date' => $this->session_date->toDateString(),
            'session_date_label' => $this->session_date->format('F j, Y'),
            'start_time' => $start,
            'end_time' => $end,
            'time_range' => $start.' – '.$end,
            'capacity' => $this->capacity,
            'court_count' => $this->court_count,
            'occupied_slots' => $occupied,
            'remaining_slots' => max(0, $this->capacity - $occupied),
            'skill_level' => $this->skill_level?->value,
            'skill_level_label' => $this->skill_level?->label(),
            'price' => round($price, 2),
            'price_formatted' => $this->price_amount === 0 ? 'Free' : '₱'.number_format($price, 2),
            'is_free' => $this->price_amount === 0,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
