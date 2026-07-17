import { useCallback, useEffect, useState } from 'react'
import { offlineDb, type QueuedOperation } from '@/shared/offline/db'
import { syncCoordinator } from '@/shared/offline/syncCoordinator'

/** Live view of one user's local sync queue, refreshed whenever the coordinator changes anything. */
export function useSyncQueue(userId: string | undefined) {
  const [queue, setQueue] = useState<QueuedOperation[]>([])

  const refresh = useCallback(async () => {
    if (!userId) {
      setQueue([])
      return
    }
    const rows = await offlineDb.syncQueue.where('userId').equals(userId).sortBy('createdAt')
    setQueue(rows)
  }, [userId])

  useEffect(() => {
    void refresh()
    return syncCoordinator.subscribe(() => void refresh())
  }, [refresh])

  return {
    queue,
    pendingCount: queue.filter((operation) => operation.status === 'queued' || operation.status === 'syncing' || operation.status === 'pending_dependency').length,
    attentionCount: queue.filter((operation) => operation.status === 'rejected' || operation.status === 'conflicted').length,
  }
}
