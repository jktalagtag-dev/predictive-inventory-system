<?php

namespace App\Domains\Sync\Models;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The server-visible lifecycle record for one offline client operation
 * (DATABASE_DESIGN.md section 10.3). The client's own queue lives in
 * IndexedDB; this row is the durable acknowledgement and conflict ledger
 * that survives client storage loss and lets the server refuse to
 * reprocess a replayed operation.
 *
 * @property int $id
 * @property string $client_operation_id
 * @property int $actor_user_id
 * @property int $branch_id
 * @property string $operation_type
 * @property string $status
 * @property string|null $dependency_operation_id
 */
class SyncOperation extends Model
{
    public const STATUSES = ['received', 'processing', 'accepted', 'rejected', 'conflicted', 'pending_dependency'];

    protected $fillable = [
        'client_operation_id', 'actor_user_id', 'branch_id', 'operation_type', 'payload_version',
        'payload_hash', 'idempotency_key_id', 'status', 'dependency_operation_id',
        'server_resource_type', 'server_resource_id', 'error_code', 'conflict_payload',
        'received_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'conflict_payload' => 'array',
            'received_at' => 'datetime',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

    public function isTerminal(): bool
    {
        return in_array($this->status, ['accepted', 'rejected', 'conflicted'], true);
    }
}
