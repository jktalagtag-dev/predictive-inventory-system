import { PanelRightOpen } from 'lucide-react'
import type { InventoryAdjustment } from '@/features/inventory/types/inventory'
import { AdjustmentStatusBadge } from '@/features/inventory/components/AdjustmentStatusBadge'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

export function AdjustmentTable({ adjustments, onView }: { adjustments: InventoryAdjustment[]; onView: (adjustment: InventoryAdjustment) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {adjustments.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No adjustments match these filters.</p>
        ) : (
          adjustments.map((adjustment) => (
            <RecordCard
              key={adjustment.id}
              ariaLabel={`View ${adjustment.adjustmentNumber}`}
              badge={<AdjustmentStatusBadge isApproved={adjustment.approvedAt !== null} status={adjustment.status} />}
              title={<span className="font-mono">{adjustment.adjustmentNumber}</span>}
              subtitle={<span className="capitalize">{adjustment.reasonCode.replace('_', ' ')}</span>}
              fields={[
                { label: 'Effective', value: adjustment.effectiveAt ? new Date(adjustment.effectiveAt).toLocaleDateString() : '—' },
                { label: 'Lines', value: adjustment.lineCount ?? '—' },
              ]}
              onClick={() => onView(adjustment)}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={750}>
          <TableHead>
            <tr>
              <TableHeaderCell>Adjustment number</TableHeaderCell>
              <TableHeaderCell>Reason</TableHeaderCell>
              <TableHeaderCell>Status</TableHeaderCell>
              <TableHeaderCell>Effective</TableHeaderCell>
              <TableHeaderCell align="right">Lines</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {adjustments.length === 0 ? (
              <TableEmptyState colSpan={6}>No adjustments match these filters.</TableEmptyState>
            ) : (
              adjustments.map((adjustment) => (
                <TableRow key={adjustment.id}>
                  <TableCell className="font-mono text-xs font-semibold text-ink">{adjustment.adjustmentNumber}</TableCell>
                  <TableCell className="capitalize text-muted">{adjustment.reasonCode.replace('_', ' ')}</TableCell>
                  <TableCell><AdjustmentStatusBadge isApproved={adjustment.approvedAt !== null} status={adjustment.status} /></TableCell>
                  <TableCell className="text-muted">{adjustment.effectiveAt ? new Date(adjustment.effectiveAt).toLocaleDateString() : '—'}</TableCell>
                  <TableCell align="right" className="text-ink">{adjustment.lineCount ?? '—'}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`View ${adjustment.adjustmentNumber}`} size="icon" variant="ghost" onClick={() => onView(adjustment)}><PanelRightOpen aria-hidden="true" size={17} /></Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </>
  )
}
