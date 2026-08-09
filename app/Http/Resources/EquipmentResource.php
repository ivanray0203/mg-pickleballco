<?php

namespace App\Http\Resources;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Equipment
 */
class EquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rate = $this->hourly_rate / 100;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'hourly_rate' => round($rate, 2),
            'hourly_rate_formatted' => '₱'.number_format($rate, 2),
            'total_quantity' => $this->total_quantity,
            'is_active' => (bool) $this->is_active,
            'image_url' => $this->image_url,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
