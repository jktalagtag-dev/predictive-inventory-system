import { useState } from 'react'
import { useAuth } from '@/features/auth/AuthProvider'
import { ConflictResolverDialog } from '@/features/sync/components/ConflictResolverDialog'
import { SyncQueueTable } from '@/features/sync/components/SyncQueueTable'
import type { QueuedOperation } from '@/shared/offline/db'
import { syncCoordinator } from '@/shared/offline/syncCoordinator'
import { useOnlineStatus } from '@/shared/offline/useOnlineStatus'
import { useSyncQueue } from '@/shared/offline/useSyncQueue'
import { PageHeader } from '@/shared/components/PageHeader'

export default function SyncQueuePage() {
  const { session } = useAuth()
  const isOnline = useOnlineStatus()
  const { queue } = useSyncQueue(session?.user.id)
  const [reviewing, setReviewing] = useState<QueuedOperation | undefined>()

  return (
    <div className="space-y-6">
      <PageHeader
        description="Operations queued on this device while offline. Accepted operations sync automatically; rejected or conflicted ones need your review."
        title="Sync queue"
      />
      <div className="rounded-lg border border-border bg-surface px-4 py-3 text-sm text-muted shadow-panel">
        {isOnline ? 'Connected — the queue syncs automatically.' : 'Offline — queued operations will sync once connectivity returns.'}
      </div>

      <SyncQueueTable
        operations={queue}
        onDiscard={(clientOperationId) => void syncCoordinator.discard(clientOperationId)}
        onReview={setReviewing}
      />

      {reviewing ? (
        <ConflictResolverDialog
          operation={reviewing}
          onClose={() => setReviewing(undefined)}
          onDiscard={() => { void syncCoordinator.discard(reviewing.clientOperationId); setReviewing(undefined) }}
          onRetry={() => { void syncCoordinator.retryAsNewOperation(reviewing.clientOperationId); setReviewing(undefined) }}
        />
      ) : null}
    </div>
  )
}
