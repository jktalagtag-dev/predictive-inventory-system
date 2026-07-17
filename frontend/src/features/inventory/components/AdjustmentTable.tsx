import { PanelRightOpen } from 'lucide-react'
import type { InventoryAdjustment } from '@/features/inventory/types/inventory'
import { AdjustmentStatusBadge } from '@/features/inventory/components/AdjustmentStatusBadge'
import { Button } from '@/shared/components/Button'

export function AdjustmentTable({ adjustments, onView }: { adjustments: InventoryAdjustment[]; onView: (adjustment: InventoryAdjustment) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[750px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr>
              <th className="px-5 py-3">Adjustment number</th>
              <th className="px-5 py-3">Reason</th>
              <th className="px-5 py-3">Status</th>
              <th className="px-5 py-3">Effective</th>
              <th className="px-5 py-3 text-right">Lines</th>
              <th className="px-5 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {adjustments.map((adjustment) => (
              <tr key={adjustment.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4 font-mono text-xs font-semibold text-ink">{adjustment.adjustmentNumber}</td>
                <td className="px-5 py-4 capitalize text-muted">{adjustment.reasonCode.replace('_', ' ')}</td>
                <td className="px-5 py-4"><AdjustmentStatusBadge isApproved={adjustment.approvedAt !== null} status={adjustment.status} /></td>
                <td className="px-5 py-4 text-muted">{adjustment.effectiveAt ? new Date(adjustment.effectiveAt).toLocaleDateString() : '—'}</td>
                <td className="px-5 py-4 text-right tabular-nums text-ink">{adjustment.lineCount ?? '—'}</td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`View ${adjustment.adjustmentNumber}`} size="icon" variant="ghost" onClick={() => onView(adjustment)}><PanelRightOpen aria-hidden="true" size={17} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
