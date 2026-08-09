<?php

namespace App\Http\Resources;

use App\Models\CourtSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourtSchedule
 */
class CourtScheduleResource extends JsonResource
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
            'day_of_week' => $this->day_of_week->value,
            'day_label' => $this->day_of_week->label(),
            'is_open' => $this->is_open,
            'opening_time' => $this->opening_time ? substr($this->opening_time, 0, 5) : null,
            'closing_time' => $this->closing_time ? substr($this->closing_time, 0, 5) : null,
        ];
    }
}
