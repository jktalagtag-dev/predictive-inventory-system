<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Governance\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AuditLog $resource
 */
class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $log = $this->resource;

        return [
            'id' => (string) $log->id,
            'actorUserId' => $log->actor_user_id !== null ? (string) $log->actor_user_id : null,
            'actorRole' => $log->actor_role_snapshot,
            'action' => $log->action,
            'entityType' => $log->entity_type,
            'entityId' => $log->entity_id !== null ? (string) $log->entity_id : null,
            'branchId' => $log->branch_id !== null ? (string) $log->branch_id : null,
            'correlationId' => $log->correlation_id,
            'schemaVersion' => $log->schema_version,
            'changes' => $log->changes,
            'createdAt' => $log->created_at?->toIso8601String(),
        ];
    }
}
