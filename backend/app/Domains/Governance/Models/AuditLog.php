<?php

namespace App\Domains\Governance\Models;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail entry. Never updated after creation; corrections
 * are new entries, not edits (CLAUDE.md section 55).
 *
 * @property int $id
 * @property int|null $actor_user_id
 * @property string|null $actor_role_snapshot
 * @property string $action
 * @property string $entity_type
 * @property int|null $entity_id
 * @property int|null $branch_id
 * @property string $correlation_id
 */
class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'actor_user_id', 'actor_role_snapshot', 'action', 'entity_type', 'entity_id',
        'branch_id', 'correlation_id', 'ip_address', 'schema_version', 'changes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
