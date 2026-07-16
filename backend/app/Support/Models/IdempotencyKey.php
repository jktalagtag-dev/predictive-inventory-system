<?php

namespace App\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Records processed write-operation keys to guarantee safe retry semantics
 * independently of the source workflow table.
 *
 * @property int $id
 * @property int $actor_user_id
 * @property string $operation_scope
 * @property string $idempotency_key
 * @property string $request_hash
 * @property string $status
 */
class IdempotencyKey extends Model
{
    protected $fillable = [
        'actor_user_id', 'operation_scope', 'idempotency_key', 'request_hash', 'status',
        'response_status_code', 'response_body', 'resource_type', 'resource_id',
        'correlation_id', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
