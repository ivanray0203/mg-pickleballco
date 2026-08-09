<?php

namespace App\Http\Resources;

use App\Models\CourtBlock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourtBlock
 */
class CourtBlockResource extends JsonResource
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
            'court_id' => $this->court_id,
            'block_date' => $this->block_date->toDateString(),
            'start_time' => substr($this->start_time, 0, 5),
            'end_time' => substr($this->end_time, 0, 5),
            'reason' => $this->reason->value,
            'reason_label' => $this->reason->label(),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
        ];
    }
}
