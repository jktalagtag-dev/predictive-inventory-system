import { CloudOff, RefreshCw } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useOnlineStatus } from '@/shared/offline/useOnlineStatus'
import { useSyncQueue } from '@/shared/offline/useSyncQueue'

export function SyncStatusIndicator({ userId }: { userId: string | undefined }) {
  const isOnline = useOnlineStatus()
  const { pendingCount, attentionCount } = useSyncQueue(userId)

  if (isOnline && pendingCount === 0 && attentionCount === 0) return null

  return (
    <Link
      className="inline-flex items-center gap-1.5 rounded-full border border-border bg-subtle px-3 py-1 text-xs font-medium text-muted transition-colors hover:text-ink"
      to="/sync"
    >
      {!isOnline ? <CloudOff aria-hidden="true" size={14} /> : <RefreshCw aria-hidden="true" size={14} />}
      {!isOnline ? 'Offline' : 'Syncing'}
      {pendingCount > 0 ? <span className="rounded-full bg-brand-50 px-1.5 py-0.5 text-brand-700">{pendingCount}</span> : null}
      {attentionCount > 0 ? <span className="rounded-full bg-danger/10 px-1.5 py-0.5 text-danger-text">{attentionCount}</span> : null}
    </Link>
  )
}
