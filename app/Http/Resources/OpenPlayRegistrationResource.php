<?php

namespace App\Http\Resources;

use App\Enums\RegistrationStatus;
use App\Models\OpenPlayRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OpenPlayRegistration
 */
class OpenPlayRegistrationResource extends JsonResource
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
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'skill_level_label' => $this->user->skill_level?->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'checked_in' => $this->status === RegistrationStatus::CheckedIn,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
        ];
    }
}
