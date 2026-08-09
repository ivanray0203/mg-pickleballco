<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array. Metadata is safe by construction
     * (no secrets are ever written to the audit log).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'actor' => $this->whenLoaded('user', fn () => $this->user?->name) ?? 'System',
            'entity' => $this->auditable_type !== null
                ? class_basename($this->auditable_type).' #'.$this->auditable_id
                : null,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'description' => Str::of($this->action)->replace(['.', '_'], ' ')->title()->toString(),
        ];
    }
}
