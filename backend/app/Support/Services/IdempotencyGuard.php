<?php

namespace App\Support\Services;

use App\Domains\Identity\Models\User;
use App\Support\Models\IdempotencyKey;
use Illuminate\Support\Facades\DB;

/**
 * Reusable retry-protection primitive for idempotency-key-guarded write
 * endpoints (adjustment posting/reversal, and future POS/receiving
 * actions). Not owned by any single domain — see LARAVEL_ARCHITECTURE.md
 * "Support" folder ownership.
 */
class IdempotencyGuard
{
    /**
     * Begins a guarded operation. Returns the stored replay response if this
     * exact request was already completed, or null if the caller should
     * proceed and call complete() afterward.
     *
     * @throws IdempotencyConflictException if the same key was submitted with a different payload,
     *                                       or is currently being processed by a concurrent request.
     */
    public function begin(User $actor, string $scope, string $key, array $payload, string $correlationId): ?array
    {
        $requestHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $scope, $key, $requestHash, $correlationId) {
            $existing = IdempotencyKey::query()
                ->where('actor_user_id', $actor->id)
                ->where('operation_scope', $scope)
                ->where('idempotency_key', $key)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->request_hash !== $requestHash) {
                    throw new IdempotencyConflictException(
                        'DUPLICATE_OPERATION',
                        409,
                        'This idempotency key was already used with a different request payload.',
                    );
                }

                if ($existing->status === 'completed') {
                    return $existing->response_body ?? [];
                }

                throw new IdempotencyConflictException(
                    'CONFLICT',
                    409,
                    'This operation is already being processed. Please retry shortly.',
                );
            }

            IdempotencyKey::query()->create([
                'actor_user_id' => $actor->id,
                'operation_scope' => $scope,
                'idempotency_key' => $key,
                'request_hash' => $requestHash,
                'status' => 'processing',
                'correlation_id' => $correlationId,
                'expires_at' => now()->addDays(7),
            ]);

            return null;
        });
    }

    public function complete(User $actor, string $scope, string $key, int $statusCode, array $responseBody, ?string $resourceType = null, ?int $resourceId = null): void
    {
        IdempotencyKey::query()
            ->where('actor_user_id', $actor->id)
            ->where('operation_scope', $scope)
            ->where('idempotency_key', $key)
            ->update([
                'status' => 'completed',
                'response_status_code' => $statusCode,
                'response_body' => $responseBody,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
            ]);
    }
}
