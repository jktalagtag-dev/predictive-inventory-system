import type { QueuedOperation, QueuedOperationStatus } from '@/shared/offline/db'

/**
 * Pure queue-selection logic, kept free of Dexie/network calls so it can
 * be unit tested directly. Deterministic ordering matters: the server
 * processes a batch in submitted array order (REST_API_SPECIFICATION.md
 * section 16.1), so the client must always offer operations in the same
 * order it created them.
 */
export function selectReadyOperations(queue: QueuedOperation[]): QueuedOperation[] {
  const acceptedIds = new Set(queue.filter((operation) => operation.status === 'accepted').map((operation) => operation.clientOperationId))

  return queue
    .filter((operation) => operation.status === 'queued')
    .filter((operation) => operation.dependencyOperationId === null || acceptedIds.has(operation.dependencyOperationId))
    .sort((a, b) => a.createdAt.localeCompare(b.createdAt))
}

const BASE_DELAY_MS = 1000
const MAX_DELAY_MS = 30_000

/** Exponential backoff with up to 20% jitter, capped at MAX_DELAY_MS. */
export function computeBackoffDelayMs(attemptCount: number): number {
  const exponential = Math.min(BASE_DELAY_MS * 2 ** attemptCount, MAX_DELAY_MS)
  const jitter = exponential * 0.2 * Math.random()
  return Math.round(exponential + jitter)
}

export type SyncOperationResult = {
  status: QueuedOperationStatus
  serverResource: { type: string; id: string } | null
  error: { code: string; conflictPayload: Record<string, unknown> | null } | null
}

export function applyResult(operation: QueuedOperation, result: SyncOperationResult, now: () => string = () => new Date().toISOString()): QueuedOperation {
  return {
    ...operation,
    status: result.status,
    lastAttemptAt: now(),
    attemptCount: operation.attemptCount + 1,
    errorCode: result.error?.code ?? null,
    conflictPayload: result.error?.conflictPayload ?? null,
    serverResource: result.serverResource,
  }
}

export function revertToQueued(operation: QueuedOperation, now: () => string = () => new Date().toISOString()): QueuedOperation {
  return { ...operation, status: 'queued', lastAttemptAt: now(), attemptCount: operation.attemptCount + 1 }
}
