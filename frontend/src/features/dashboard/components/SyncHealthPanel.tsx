import { RefreshCw } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { SyncHealth } from '@/features/dashboard/types/dashboard'

export function SyncHealthPanel({ health }: { health: SyncHealth }) {
  const hasAttention = health.conflictedCount > 0 || health.rejectedCount > 0

  return (
    <section className="rounded-xl border border-border bg-surface p-5 shadow-panel">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h2 className="text-base font-semibold text-ink">Sync health</h2>
          <p className="mt-1 text-sm text-muted">Branch-wide offline operation status.</p>
        </div>
        <Link className="text-sm font-medium text-brand-700 hover:underline" to="/sync">View queue</Link>
      </div>
      <dl className="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
        <div>
          <dt className="text-muted">Pending</dt>
          <dd className="mt-1 text-lg font-semibold tabular-nums text-ink">{health.pendingCount}</dd>
        </div>
        <div>
          <dt className="text-muted">Accepted</dt>
          <dd className="mt-1 text-lg font-semibold tabular-nums text-emerald-700">{health.acceptedCount}</dd>
        </div>
        <div>
          <dt className="text-muted">Conflicted</dt>
          <dd className="mt-1 text-lg font-semibold tabular-nums text-amber-700">{health.conflictedCount}</dd>
        </div>
        <div>
          <dt className="text-muted">Rejected</dt>
          <dd className="mt-1 text-lg font-semibold tabular-nums text-red-700">{health.rejectedCount}</dd>
        </div>
      </dl>
      {hasAttention ? (
        <p className="mt-4 flex items-center gap-1.5 text-sm text-amber-800" role="status">
          <RefreshCw aria-hidden="true" size={14} /> Some offline operations need review.
        </p>
      ) : null}
      {health.lastReceivedAt ? <p className="mt-3 text-xs text-muted">Last received {new Date(health.lastReceivedAt).toLocaleString()}</p> : null}
    </section>
  )
}
