import { RotateCcw, Trash2 } from 'lucide-react'
import type { QueuedOperation } from '@/shared/offline/db'
import { SyncQueueStatusBadge } from '@/features/sync/components/SyncQueueStatusBadge'
import { Button } from '@/shared/components/Button'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

type SyncQueueTableProps = {
  operations: QueuedOperation[]
  onReview: (operation: QueuedOperation) => void
  onDiscard: (clientOperationId: string) => void
}

export function SyncQueueTable({ operations, onReview, onDiscard }: SyncQueueTableProps) {
  return (
    <Table minWidth={700}>
      <TableHead>
        <tr>
          <TableHeaderCell>Operation</TableHeaderCell>
          <TableHeaderCell>Queued</TableHeaderCell>
          <TableHeaderCell>Status</TableHeaderCell>
          <TableHeaderCell align="right">Attempts</TableHeaderCell>
          <TableHeaderCell align="right">Actions</TableHeaderCell>
        </tr>
      </TableHead>
      <TableBody>
        {operations.length === 0 ? (
          <TableEmptyState colSpan={5}>Nothing queued on this device.</TableEmptyState>
        ) : operations.map((operation) => (
          <TableRow key={operation.clientOperationId}>
            <TableCell>
              <p className="font-medium text-ink">{operation.summary}</p>
              <p className="font-mono text-xs text-muted">{operation.clientOperationId.slice(0, 8)}</p>
            </TableCell>
            <TableCell><span className="whitespace-nowrap tabular-nums">{new Date(operation.createdAt).toLocaleString()}</span></TableCell>
            <TableCell><SyncQueueStatusBadge status={operation.status} /></TableCell>
            <TableCell align="right">{operation.attemptCount}</TableCell>
            <TableCell align="right">
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
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  )
}
