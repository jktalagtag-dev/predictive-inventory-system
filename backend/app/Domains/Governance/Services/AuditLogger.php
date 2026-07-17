<?php

namespace App\Domains\Governance\Services;

use App\Domains\Governance\Models\AuditLog;
use App\Domains\Identity\Models\User;

/**
 * Append-only audit trail writer. Callers invoke record() from inside the
 * same database transaction as the business fact it describes, so the
 * audit entry commits atomically with the action (CLAUDE.md section 55;
 * DEVELOPMENT_ROADMAP.md M4/M5 acceptance criteria require linked audit
 * events for every posted receipt, adjustment, and approval).
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before  Curated pre-action field snapshot, never a raw model dump.
     * @param  array<string, mixed>|null  $after  Curated post-action field snapshot.
     * @param  array<string, mixed>|null  $metadata  Additional non-sensitive operational context.
     */
    public function record(
        User $actor,
        string $action,
        string $entityType,
        ?int $entityId,
        ?int $branchId,
        string $correlationId,
        ?array $before = null,
        ?array $after = null,
        ?string $ipAddress = null,
        ?array $metadata = null,
    ): AuditLog {
        $changes = array_filter(['before' => $before, 'after' => $after], fn ($value) => $value !== null);

        return AuditLog::query()->create([
            'actor_user_id' => $actor->id,
            'actor_role_snapshot' => $actor->activeRoles()->pluck('code')->implode(','),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'branch_id' => $branchId,
            'correlation_id' => $correlationId,
            'ip_address' => $ipAddress,
            'schema_version' => 1,
            'changes' => $changes === [] ? null : $changes,
            'metadata' => $metadata,
        ]);
    }
}
