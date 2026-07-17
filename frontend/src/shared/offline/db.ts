import Dexie, { type Table } from 'dexie'

/**
 * Mirrors the server's sync_operations status vocabulary
 * (DATABASE_DESIGN.md section 10.3) plus two purely local states:
 * 'queued' (never yet sent) and 'syncing' (in flight). Every other value
 * is a status the server itself returned.
 */
export type QueuedOperationStatus = 'queued' | 'syncing' | 'accepted' | 'rejected' | 'conflicted' | 'pending_dependency'

export type QueuedOperation = {
  clientOperationId: string
  userId: string
  operationType: string
  branchId: string
  payloadVersion: number
  idempotencyKey: string
  dependencyOperationId: string | null
  payload: Record<string, unknown>
  /** Human-readable label for the sync queue UI, e.g. "Adjustment: 3 lines". */
  summary: string
  status: QueuedOperationStatus
  createdAt: string
  lastAttemptAt: string | null
  attemptCount: number
  errorCode: string | null
  conflictPayload: Record<string, unknown> | null
  serverResource: { type: string; id: string } | null
}

export type CachedProduct = {
  id: string
  userId: string
  sku: string
  name: string
}

export type UserSyncMeta = {
  userId: string
  lastSyncAt: string | null
  productCacheUpdatedAt: string | null
}

/**
 * Versioned IndexedDB schema for approved offline workflows
 * (CLAUDE.md section 41). All three tables are scoped by userId so
 * clearOfflineDataForUser() can remove one user's cached data on logout
 * or user switch without touching another user's queue
 * (DEVELOPMENT_ROADMAP.md M9 acceptance criteria).
 */
class OfflineDatabase extends Dexie {
  syncQueue!: Table<QueuedOperation, string>

  productCache!: Table<CachedProduct, string>

  meta!: Table<UserSyncMeta, string>

  constructor() {
    super('predictive-inventory-offline')

    this.version(1).stores({
      syncQueue: 'clientOperationId, userId, status, operationType, dependencyOperationId, createdAt',
      productCache: 'id, userId, sku',
      meta: 'userId',
    })
  }
}

export const offlineDb = new OfflineDatabase()

export async function clearOfflineDataForUser(userId: string): Promise<void> {
  await Promise.all([
    offlineDb.syncQueue.where('userId').equals(userId).delete(),
    offlineDb.productCache.where('userId').equals(userId).delete(),
    offlineDb.meta.where('userId').equals(userId).delete(),
  ])
}
