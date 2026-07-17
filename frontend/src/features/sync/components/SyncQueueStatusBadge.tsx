import type { QueuedOperationStatus } from '@/shared/offline/db'

const labels: Record<QueuedOperationStatus, string> = {
  queued: 'Queued',
  syncing: 'Syncing…',
  accepted: 'Synced',
  rejected: 'Rejected',
  conflicted: 'Needs review',
  pending_dependency: 'Waiting on prior step',
}

const classes: Record<QueuedOperationStatus, string> = {
  queued: 'bg-slate-100 text-slate-700',
  syncing: 'bg-blue-100 text-blue-700',
  accepted: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-red-100 text-red-700',
  conflicted: 'bg-amber-100 text-amber-800',
  pending_dependency: 'bg-slate-100 text-slate-600',
}

export function SyncQueueStatusBadge({ status }: { status: QueuedOperationStatus }) {
  return <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${classes[status]}`}>{labels[status]}</span>
}
