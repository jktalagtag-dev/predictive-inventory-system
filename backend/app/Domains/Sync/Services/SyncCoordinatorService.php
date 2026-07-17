<?php

namespace App\Domains\Sync\Services;

use App\Domains\Governance\Services\AuditLogger;
use App\Domains\Identity\Models\User;
use App\Domains\Sync\Contracts\OfflineOperationHandler;
use App\Domains\Sync\Models\SyncOperation;
use App\Domains\Sync\Support\OfflineOperationRegistry;
use App\Support\Services\IdempotencyConflictException;
use App\Support\Services\IdempotencyGuard;

/**
 * Processes one synchronize-queued-operations batch in the client's
 * submitted dependency order (REST_API_SPECIFICATION.md section 16.1).
 * Every operation gets a terminal or pending_dependency result — a
 * failure on one operation never aborts the batch, so unrelated
 * operations are still reported deterministically.
 *
 * Two independent replay guards are checked per operation: the
 * sync_operations row keyed by client_operation_id (the sync ledger's
 * own dedup key) and IdempotencyGuard keyed by idempotency_key (the same
 * mechanism every other write endpoint uses) — DATABASE_DESIGN.md
 * section 10.3 requires both fields, and REST_API_SPECIFICATION.md
 * section 16.1 explicitly says either one carrying a changed payload
 * hash must be refused.
 */
class SyncCoordinatorService
{
    public function __construct(
        private readonly IdempotencyGuard $idempotencyGuard,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /**
     * @param  array<int, array{clientOperationId:string, operationType:string, branchId:int, payloadVersion:int, idempotencyKey:string, dependencyOperationId:?string, payload:array}>  $operations
     * @return array<string, array{status:string, serverResource:?array, error:?array, serverVersion:?int}>
     */
    public function processBatch(array $operations, User $actor, string $correlationId): array
    {
        $results = [];
        $acceptedInBatch = [];

        foreach ($operations as $operation) {
            $results[$operation['clientOperationId']] = $this->processOne($operation, $actor, $correlationId, $acceptedInBatch);
        }

        return $results;
    }

    /**
     * @param  array<string, bool>  $acceptedInBatch
     * @return array{status:string, serverResource:?array, error:?array, serverVersion:?int}
     */
    private function processOne(array $operation, User $actor, string $correlationId, array &$acceptedInBatch): array
    {
        $clientOperationId = $operation['clientOperationId'];
        $payloadHash = hash('sha256', json_encode($operation['payload'], JSON_THROW_ON_ERROR));

        $existing = SyncOperation::query()->where('client_operation_id', $clientOperationId)->first();

        if ($existing !== null) {
            if ($existing->payload_hash !== $payloadHash) {
                return $this->formatFromRow($this->persist($operation, $payloadHash, $actor, 'rejected', null, null, 'DUPLICATE_OPERATION', null));
            }

            if ($existing->isTerminal()) {
                if ($existing->status === 'accepted') {
                    $acceptedInBatch[$clientOperationId] = true;
                }

                return $this->formatFromRow($existing);
            }

            // status was 'pending_dependency' from an earlier batch — fall
            // through and re-evaluate it now that more context may exist.
        }

        $definition = OfflineOperationRegistry::find($operation['operationType']);

        if ($definition === null) {
            return $this->formatFromRow($this->persist($operation, $payloadHash, $actor, 'rejected', null, null, 'UNSUPPORTED_OFFLINE_OPERATION', null));
        }

        if ($operation['payloadVersion'] !== $definition['currentPayloadVersion']) {
            return $this->formatFromRow($this->persist($operation, $payloadHash, $actor, 'rejected', null, null, 'UNSUPPORTED_PAYLOAD_VERSION', null));
        }

        if (! $actor->hasPermission($definition['permission']) || ! $actor->canAccessBranch($operation['branchId'])) {
            return $this->formatFromRow($this->persist($operation, $payloadHash, $actor, 'rejected', null, null, 'FORBIDDEN', null));
        }

        $dependencyId = $operation['dependencyOperationId'] ?? null;
        if ($dependencyId !== null && ! ($acceptedInBatch[$dependencyId] ?? $this->isDependencyAccepted($dependencyId))) {
            return $this->formatFromRow($this->persist($operation, $payloadHash, $actor, 'pending_dependency', null, null, null, null));
        }

        try {
            $replay = $this->idempotencyGuard->begin($actor, 'sync.'.$operation['operationType'], $operation['idempotencyKey'], $operation['payload'], $correlationId);
        } catch (IdempotencyConflictException $exception) {
            return $this->formatFromRow($this->persist($operation, $payloadHash, $actor, 'rejected', null, null, $exception->errorCode(), null));
        }

        if ($replay !== null) {
            $acceptedInBatch[$clientOperationId] = true;
            $row = $this->persist(
                $operation, $payloadHash, $actor, 'accepted',
                $replay['data']['resourceType'] ?? null, $replay['data']['resourceId'] ?? null, null, null,
            );

            return $this->formatFromRow($row);
        }

        /** @var OfflineOperationHandler $handler */
        $handler = app($definition['handler']);

        try {
            $result = $handler->handle($operation['payload'], $operation['branchId'], $actor, $correlationId);
        } catch (SyncOperationConflictException $exception) {
            $row = $this->persist($operation, $payloadHash, $actor, 'conflicted', null, null, $exception->errorCode(), $exception->conflictPayload());
            $this->auditLogger->record(
                $actor, 'sync_operation.conflicted', 'sync_operation', $row->id, $operation['branchId'], $correlationId,
                null, ['operationType' => $operation['operationType'], 'errorCode' => $exception->errorCode()],
            );

            return $this->formatFromRow($row);
        } catch (SyncOperationRejectedException $exception) {
            $row = $this->persist($operation, $payloadHash, $actor, 'rejected', null, null, $exception->errorCode(), null);
            $this->auditLogger->record(
                $actor, 'sync_operation.rejected', 'sync_operation', $row->id, $operation['branchId'], $correlationId,
                null, ['operationType' => $operation['operationType'], 'errorCode' => $exception->errorCode()],
            );

            return $this->formatFromRow($row);
        }

        $this->idempotencyGuard->complete(
            $actor, 'sync.'.$operation['operationType'], $operation['idempotencyKey'], 200,
            ['data' => ['resourceType' => $result->resourceType, 'resourceId' => $result->resourceId]],
            $result->resourceType, $result->resourceId,
        );

        $row = $this->persist($operation, $payloadHash, $actor, 'accepted', $result->resourceType, $result->resourceId, null, null);
        $acceptedInBatch[$clientOperationId] = true;

        $this->auditLogger->record(
            $actor, 'sync_operation.accepted', 'sync_operation', $row->id, $operation['branchId'], $correlationId,
            null, ['operationType' => $operation['operationType'], 'resourceType' => $result->resourceType, 'resourceId' => $result->resourceId],
        );

        return $this->formatFromRow($row);
    }

    private function isDependencyAccepted(string $dependencyClientOperationId): bool
    {
        return SyncOperation::query()
            ->where('client_operation_id', $dependencyClientOperationId)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $operation
     */
    private function persist(
        array $operation,
        string $payloadHash,
        User $actor,
        string $status,
        ?string $resourceType,
        ?int $resourceId,
        ?string $errorCode,
        ?array $conflictPayload,
    ): SyncOperation {
        $row = SyncOperation::query()->where('client_operation_id', $operation['clientOperationId'])->first();

        if ($row === null) {
            $row = new SyncOperation(['client_operation_id' => $operation['clientOperationId']]);
            $row->received_at = now();
        }

        $row->actor_user_id = $actor->id;
        $row->branch_id = $operation['branchId'];
        $row->operation_type = $operation['operationType'];
        $row->payload_version = $operation['payloadVersion'];
        $row->payload_hash = $payloadHash;
        $row->status = $status;
        $row->dependency_operation_id = $operation['dependencyOperationId'] ?? null;
        $row->server_resource_type = $resourceType;
        $row->server_resource_id = $resourceId;
        $row->error_code = $errorCode;
        $row->conflict_payload = $conflictPayload;

        if (in_array($status, ['accepted', 'rejected', 'conflicted'], true)) {
            $row->resolved_at = now();
        }

        $row->save();

        return $row;
    }

    /**
     * @return array{status:string, serverResource:?array, error:?array, serverVersion:?int}
     */
    private function formatFromRow(SyncOperation $row): array
    {
        return [
            'status' => $row->status,
            'serverResource' => $row->server_resource_type !== null ? ['type' => $row->server_resource_type, 'id' => (string) $row->server_resource_id] : null,
            'error' => $row->error_code !== null ? ['code' => $row->error_code, 'conflictPayload' => $row->conflict_payload] : null,
            'serverVersion' => null,
        ];
    }
}
