import { PanelRightOpen } from 'lucide-react'
import type { PurchaseOrder } from '@/features/purchase-orders/types/purchaseOrder'
import { PurchaseOrderStatusBadge } from '@/features/purchase-orders/components/PurchaseOrderStatusBadge'
import { Button } from '@/shared/components/Button'

export function PurchaseOrderTable({ purchaseOrders, onView }: { purchaseOrders: PurchaseOrder[]; onView: (po: PurchaseOrder) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[850px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr><th className="px-5 py-3">PO number</th><th className="px-5 py-3">Supplier</th><th className="px-5 py-3">Status</th><th className="px-5 py-3 text-right">Total</th><th className="px-5 py-3">Expected receipt</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {purchaseOrders.map((po) => (
              <tr key={po.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4 font-mono text-xs font-semibold text-ink">{po.poNumber}</td>
                <td className="px-5 py-4 text-muted">{po.supplier?.legalName ?? '—'}</td>
                <td className="px-5 py-4"><PurchaseOrderStatusBadge status={po.status} /></td>
                <td className="px-5 py-4 text-right tabular-nums text-ink">{po.currencyCode} {po.totalAmount}</td>
                <td className="px-5 py-4 text-muted">{po.expectedReceiptAt ? new Date(po.expectedReceiptAt).toLocaleDateString() : '—'}</td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`View ${po.poNumber}`} size="icon" variant="ghost" onClick={() => onView(po)}><PanelRightOpen aria-hidden="true" size={17} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
