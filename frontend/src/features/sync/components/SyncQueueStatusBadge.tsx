import type { QueuedOperationStatus } from '@/shared/offline/db'
import { Badge, type BadgeTone } from '@/shared/components/Badge'

const labels: Record<QueuedOperationStatus, string> = {
  queued: 'Queued',
  syncing: 'Syncing…',
  accepted: 'Synced',
  rejected: 'Rejected',
  conflicted: 'Needs review',
  pending_dependency: 'Waiting on prior step',
}

const tones: Record<QueuedOperationStatus, BadgeTone> = {
  queued: 'neutral',
  syncing: 'info',
  accepted: 'success',
  rejected: 'danger',
  conflicted: 'warning',
  pending_dependency: 'neutral',
}

export function SyncQueueStatusBadge({ status }: { status: QueuedOperationStatus }) {
  return <Badge tone={tones[status]}>{labels[status]}</Badge>
}
