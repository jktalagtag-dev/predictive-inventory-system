import { apiClient } from '@/shared/api/client'
import { offlineDb, type QueuedOperation } from '@/shared/offline/db'
import { applyResult, computeBackoffDelayMs, selectReadyOperations, revertToQueued, type SyncOperationResult } from '@/shared/offline/syncLogic'

const MAX_BATCH_SIZE = 20
const MAX_RETRYABLE_ATTEMPTS = 8

type Listener = () => void

/**
 * Drives the queued-operation lifecycle: waits for connectivity, submits
 * ready batches in deterministic order, applies server results back onto
 * the Dexie queue, and reschedules itself with bounded exponential
 * backoff after a network-level failure. A per-operation terminal result
 * from the server (accepted/rejected/conflicted) is never retried
 * automatically — only the inability to reach the server at all is
 * (DEVELOPMENT_ROADMAP.md M9 acceptance criteria).
 */
class SyncCoordinator {
  private readonly listeners = new Set<Listener>();
  private isSyncing = false;
  private retryTimer: ReturnType<typeof setTimeout> | null = null;

  constructor() {
    if (typeof window !== 'undefined') {
      window.addEventListener('online', () => this.currentUserId && this.triggerSync(this.currentUserId));
    }
  }

  private currentUserId: string | null = null;

  setActiveUser(userId: string | null): void {
    this.currentUserId = userId;
    if (userId) void this.triggerSync(userId);
  }

  subscribe(listener: Listener): () => void {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }

  private notify(): void {
    this.listeners.forEach((listener) => listener());
  }

  async enqueue(
    operation: Pick<QueuedOperation, 'clientOperationId' | 'userId' | 'operationType' | 'branchId' | 'payloadVersion' | 'idempotencyKey' | 'dependencyOperationId' | 'payload' | 'summary'>,
  ): Promise<void> {
    await offlineDb.syncQueue.add({
      ...operation,
      status: 'queued',
      createdAt: new Date().toISOString(),
      lastAttemptAt: null,
      attemptCount: 0,
      errorCode: null,
      conflictPayload: null,
      serverResource: null,
    });
    this.notify();
    void this.triggerSync(operation.userId);
  }

  async discard(clientOperationId: string): Promise<void> {
    await offlineDb.syncQueue.delete(clientOperationId);
    this.notify();
  }

  /** Resubmits a conflicted or rejected operation as a brand-new operation, since a resolved conflict is a new user decision, not a mutation of the original immutable record. */
  async retryAsNewOperation(clientOperationId: string): Promise<void> {
    const original = await offlineDb.syncQueue.get(clientOperationId);
    if (!original) return;

    await offlineDb.syncQueue.delete(clientOperationId);
    await this.enqueue({
      clientOperationId: crypto.randomUUID(),
      userId: original.userId,
      operationType: original.operationType,
      branchId: original.branchId,
      payloadVersion: original.payloadVersion,
      idempotencyKey: crypto.randomUUID(),
      dependencyOperationId: null,
      payload: original.payload,
      summary: original.summary,
    });
  }

  async triggerSync(userId: string): Promise<void> {
    if (this.isSyncing || typeof navigator !== 'undefined' && !navigator.onLine) return;

    this.isSyncing = true;
    try {
      await this.syncLoop(userId);
    } finally {
      this.isSyncing = false;
      this.notify();
    }
  }

  private async syncLoop(userId: string): Promise<void> {
    for (;;) {
      const queue = await offlineDb.syncQueue.where('userId').equals(userId).toArray();
      const ready = selectReadyOperations(queue).slice(0, MAX_BATCH_SIZE);
      if (ready.length === 0) return;

      await offlineDb.syncQueue.bulkPut(ready.map((operation) => ({ ...operation, status: 'syncing' as const })));
      this.notify();

      let results: Record<string, SyncOperationResult>;
      try {
        const response = await apiClient.post<{ data: { results: Record<string, SyncOperationResult> } }>('/sync/operations', {
          operations: ready.map((operation) => ({
            clientOperationId: operation.clientOperationId,
            operationType: operation.operationType,
            branchId: operation.branchId,
            payloadVersion: operation.payloadVersion,
            idempotencyKey: operation.idempotencyKey,
            dependencyOperationId: operation.dependencyOperationId,
            payload: operation.payload,
          })),
        });
        results = response.data.data.results;
      } catch {
        await offlineDb.syncQueue.bulkPut(ready.map((operation) => revertToQueued(operation)));
        this.scheduleRetry(userId, ready[0].attemptCount);
        this.notify();
        return;
      }

      const updated = ready
        .filter((operation) => results[operation.clientOperationId] !== undefined)
        .map((operation) => applyResult(operation, results[operation.clientOperationId]));
      await offlineDb.syncQueue.bulkPut(updated);
      await offlineDb.meta.put({ userId, lastSyncAt: new Date().toISOString(), productCacheUpdatedAt: (await offlineDb.meta.get(userId))?.productCacheUpdatedAt ?? null });
      this.notify();
    }
  }

  private scheduleRetry(userId: string, attemptCount: number): void {
    if (attemptCount >= MAX_RETRYABLE_ATTEMPTS) return;
    if (this.retryTimer) clearTimeout(this.retryTimer);
    this.retryTimer = setTimeout(() => void this.triggerSync(userId), computeBackoffDelayMs(attemptCount));
  }
}

export const syncCoordinator = new SyncCoordinator();
