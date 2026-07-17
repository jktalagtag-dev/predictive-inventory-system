import { describe, expect, it } from 'vitest'
import { applyResult, computeBackoffDelayMs, revertToQueued, selectReadyOperations, type SyncOperationResult } from '@/shared/offline/syncLogic'
import type { QueuedOperation } from '@/shared/offline/db'

function buildOperation(overrides: Partial<QueuedOperation> = {}): QueuedOperation {
  return {
    clientOperationId: 'op-1',
    userId: 'user-1',
    operationType: 'inventory_adjustment.create',
    branchId: 'branch-1',
    payloadVersion: 1,
    idempotencyKey: 'key-1',
    dependencyOperationId: null,
    payload: {},
    summary: 'Adjustment: 1 line',
    status: 'queued',
    createdAt: '2026-07-17T10:00:00.000Z',
    lastAttemptAt: null,
    attemptCount: 0,
    errorCode: null,
    conflictPayload: null,
    serverResource: null,
    ...overrides,
  }
}

describe('selectReadyOperations', () => {
  it('returns only queued operations, sorted oldest first', () => {
    const queue = [
      buildOperation({ clientOperationId: 'op-2', createdAt: '2026-07-17T10:02:00.000Z' }),
      buildOperation({ clientOperationId: 'op-1', createdAt: '2026-07-17T10:00:00.000Z' }),
      buildOperation({ clientOperationId: 'op-3', createdAt: '2026-07-17T10:01:00.000Z', status: 'accepted' }),
    ]

    const ready = selectReadyOperations(queue)

    expect(ready.map((operation) => operation.clientOperationId)).toEqual(['op-1', 'op-2'])
  })

  it('excludes a dependent operation whose prerequisite has not been accepted yet', () => {
    const queue = [
      buildOperation({ clientOperationId: 'prereq', status: 'queued' }),
      buildOperation({ clientOperationId: 'dependent', dependencyOperationId: 'prereq', createdAt: '2026-07-17T10:00:01.000Z' }),
    ]

    const ready = selectReadyOperations(queue)

    expect(ready.map((operation) => operation.clientOperationId)).toEqual(['prereq'])
  })

  it('includes a dependent operation once its prerequisite is accepted', () => {
    const queue = [
      buildOperation({ clientOperationId: 'prereq', status: 'accepted' }),
      buildOperation({ clientOperationId: 'dependent', dependencyOperationId: 'prereq', createdAt: '2026-07-17T10:00:01.000Z' }),
    ]

    const ready = selectReadyOperations(queue)

    expect(ready.map((operation) => operation.clientOperationId)).toEqual(['dependent'])
  })

  it('permanently excludes a dependent operation whose prerequisite was rejected', () => {
    const queue = [
      buildOperation({ clientOperationId: 'prereq', status: 'rejected' }),
      buildOperation({ clientOperationId: 'dependent', dependencyOperationId: 'prereq', createdAt: '2026-07-17T10:00:01.000Z' }),
    ]

    const ready = selectReadyOperations(queue)

    expect(ready).toEqual([])
  })

  it('excludes operations that are syncing, conflicted, or pending_dependency', () => {
    const queue = [
      buildOperation({ clientOperationId: 'a', status: 'syncing' }),
      buildOperation({ clientOperationId: 'b', status: 'conflicted' }),
      buildOperation({ clientOperationId: 'c', status: 'pending_dependency' }),
    ]

    expect(selectReadyOperations(queue)).toEqual([])
  })
})

describe('computeBackoffDelayMs', () => {
  it('grows exponentially with attempt count', () => {
    const delay0 = computeBackoffDelayMs(0)
    const delay3 = computeBackoffDelayMs(3)

    expect(delay0).toBeGreaterThanOrEqual(1000)
    expect(delay0).toBeLessThanOrEqual(1200)
    expect(delay3).toBeGreaterThanOrEqual(8000)
    expect(delay3).toBeLessThanOrEqual(9600)
  })

  it('caps the delay at 30 seconds plus jitter regardless of attempt count', () => {
    const delay = computeBackoffDelayMs(20)

    expect(delay).toBeGreaterThanOrEqual(30_000)
    expect(delay).toBeLessThanOrEqual(36_000)
  })
})

describe('applyResult', () => {
  it('maps an accepted result onto the operation and increments attemptCount', () => {
    const operation = buildOperation({ attemptCount: 1 })
    const result: SyncOperationResult = { status: 'accepted', serverResource: { type: 'inventory_adjustment', id: '42' }, error: null }

    const updated = applyResult(operation, result, () => '2026-07-17T11:00:00.000Z')

    expect(updated.status).toBe('accepted')
    expect(updated.serverResource).toEqual({ type: 'inventory_adjustment', id: '42' })
    expect(updated.attemptCount).toBe(2)
    expect(updated.lastAttemptAt).toBe('2026-07-17T11:00:00.000Z')
    expect(updated.errorCode).toBeNull()
  })

  it('maps a conflicted result including its conflict payload', () => {
    const operation = buildOperation()
    const result: SyncOperationResult = {
      status: 'conflicted',
      serverResource: null,
      error: { code: 'STALE_STOCK_SNAPSHOT', conflictPayload: { reason: 'stock changed' } },
    }

    const updated = applyResult(operation, result)

    expect(updated.status).toBe('conflicted')
    expect(updated.errorCode).toBe('STALE_STOCK_SNAPSHOT')
    expect(updated.conflictPayload).toEqual({ reason: 'stock changed' })
  })
})

describe('revertToQueued', () => {
  it('resets status to queued and increments attemptCount after a network failure', () => {
    const operation = buildOperation({ status: 'syncing', attemptCount: 2 })

    const reverted = revertToQueued(operation, () => '2026-07-17T11:05:00.000Z')

    expect(reverted.status).toBe('queued')
    expect(reverted.attemptCount).toBe(3)
    expect(reverted.lastAttemptAt).toBe('2026-07-17T11:05:00.000Z')
  })
})
