<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function record(Model $model, string $event, array $oldValues = [], array $newValues = []): AuditLog
    {
        return $this->recordActivity($event, $model, $oldValues, $newValues);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function recordActivity(
        string $event,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        ?int $actorId = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $actorId ?? Auth::id(),
            'auditable_type' => $subject?->getMorphClass() ?? 'system',
            'auditable_id' => $subject?->getKey() ?? 0,
            'event' => $event,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
        ]);
    }
}
