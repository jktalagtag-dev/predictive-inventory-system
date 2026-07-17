import { RotateCcw, Trash2 } from 'lucide-react'
import type { QueuedOperation } from '@/shared/offline/db'
import { SyncQueueStatusBadge } from '@/features/sync/components/SyncQueueStatusBadge'
import { Button } from '@/shared/components/Button'

type SyncQueueTableProps = {
  operations: QueuedOperation[]
  onReview: (operation: QueuedOperation) => void
  onDiscard: (clientOperationId: string) => void
}

export function SyncQueueTable({ operations, onReview, onDiscard }: SyncQueueTableProps) {
  if (operations.length === 0) {
    return <p className="rounded-xl border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">Nothing queued on this device.</p>
  }

  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[700px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr>
              <th className="px-4 py-3">Operation</th>
              <th className="px-4 py-3">Queued</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3 text-right">Attempts</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {operations.map((operation) => (
              <tr key={operation.clientOperationId} className="hover:bg-subtle/70">
                <td className="px-4 py-3">
                  <p className="font-medium text-ink">{operation.summary}</p>
                  <p className="font-mono text-xs text-muted">{operation.clientOperationId.slice(0, 8)}</p>
                </td>
                <td className="px-4 py-3 whitespace-nowrap tabular-nums">{new Date(operation.createdAt).toLocaleString()}</td>
                <td className="px-4 py-3"><SyncQueueStatusBadge status={operation.status} /></td>
                <td className="px-4 py-3 text-right tabular-nums">{operation.attemptCount}</td>
                <td className="px-4 py-3">
                  <div className="flex justify-end gap-1">
                    {operation.status === 'conflicted' || operation.status === 'rejected' ? (
                      <Button aria-label="Review" size="icon" variant="ghost" onClick={() => onReview(operation)}>
                        <RotateCcw aria-hidden="true" size={16} />
                      </Button>
                    ) : null}
                    {operation.status !== 'syncing' ? (
                      <Button aria-label="Discard" size="icon" variant="ghost" onClick={() => onDiscard(operation.clientOperationId)}>
                        <Trash2 aria-hidden="true" size={16} />
                      </Button>
                    ) : null}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
