<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records a lightweight, append-only trail of important admin actions. Only
 * safe context is ever stored — never secrets or payment payloads.
 */
class AuditLogger
{
    /**
     * Record an action, optionally tied to an entity, attributed to the
     * currently authenticated user.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(string $action, ?Model $entity = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $entity?->getMorphClass(),
            'auditable_id' => $entity?->getKey(),
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);
    }
}
