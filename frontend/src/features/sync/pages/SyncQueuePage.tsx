import { useState } from 'react'
import { useAuth } from '@/features/auth/AuthProvider'
import { ConflictResolverDialog } from '@/features/sync/components/ConflictResolverDialog'
import { SyncQueueTable } from '@/features/sync/components/SyncQueueTable'
import type { QueuedOperation } from '@/shared/offline/db'
import { syncCoordinator } from '@/shared/offline/syncCoordinator'
import { useOnlineStatus } from '@/shared/offline/useOnlineStatus'
import { useSyncQueue } from '@/shared/offline/useSyncQueue'
import { PageHeader } from '@/shared/components/PageHeader'
import { useToast } from '@/shared/components/Toast'

export default function SyncQueuePage() {
  const { session } = useAuth()
  const isOnline = useOnlineStatus()
  const { queue } = useSyncQueue(session?.user.id)
  const [reviewing, setReviewing] = useState<QueuedOperation | undefined>()
  const { toast } = useToast()

  const discard = (clientOperationId: string) => {
    void syncCoordinator.discard(clientOperationId)
    toast({ title: 'Operation discarded', variant: 'info' })
  }

  const retry = (clientOperationId: string) => {
    void syncCoordinator.retryAsNewOperation(clientOperationId)
    toast({ title: 'Queued for retry', description: 'It will sync the next time this device is online.', variant: 'info' })
  }

  return (
    <div className="space-y-6">
      <PageHeader
        description="Operations queued on this device while offline. Accepted operations sync automatically; rejected or conflicted ones need your review."
        title="Sync queue"
      />
      <div className="rounded-lg border border-border bg-surface px-4 py-3 text-sm text-muted shadow-panel">
        {isOnline ? 'Connected — the queue syncs automatically.' : 'Offline — queued operations will sync once connectivity returns.'}
      </div>

      <SyncQueueTable operations={queue} onDiscard={discard} onReview={setReviewing} />

      {reviewing ? (
        <ConflictResolverDialog
          operation={reviewing}
          onClose={() => setReviewing(undefined)}
          onDiscard={() => { discard(reviewing.clientOperationId); setReviewing(undefined) }}
          onRetry={() => { retry(reviewing.clientOperationId); setReviewing(undefined) }}
        />
      ) : null}
    </div>
  )
}
