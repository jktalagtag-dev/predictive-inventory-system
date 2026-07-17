<?php

namespace App\Domains\Sync\Contracts;

use App\Domains\Identity\Models\User;

/**
 * One handler per approved offline operation type. A handler owns
 * payload validation and delegates the actual business action to the
 * same domain service the online endpoint uses, so offline and online
 * writes are always validated by one source of truth
 * (CLAUDE.md section 8, "single source of truth ... business calculations").
 */
interface OfflineOperationHandler
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws \App\Domains\Sync\Services\SyncOperationRejectedException on validation/business refusal
     * @throws \App\Domains\Sync\Services\SyncOperationConflictException when the payload conflicts with current server state
     */
    public function handle(array $payload, int $branchId, User $actor, string $correlationId): OfflineOperationResult;
}
