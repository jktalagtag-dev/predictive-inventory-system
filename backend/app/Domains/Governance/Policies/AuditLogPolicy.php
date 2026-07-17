<?php

namespace App\Domains\Governance\Policies;

use App\Domains\Governance\Models\AuditLog;
use App\Domains\Identity\Models\User;

/**
 * Audit access is restricted to Owner/Manager regardless of the audit.read
 * grant, per REST_API_SPECIFICATION.md section 14.1 ("Owner or authorized
 * Manager only").
 */
class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('audit.read') && ($actor->hasRole('owner') || $actor->hasRole('manager'));
    }

    public function view(User $actor, AuditLog $auditLog): bool
    {
        return $this->viewAny($actor);
    }
}
