import { ClipboardList } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { PendingPurchaseOrderItem } from '@/features/dashboard/types/dashboard'

type PendingPurchaseOrdersPanelProps = { count: number; items: PendingPurchaseOrderItem[] }

export function PendingPurchaseOrdersPanel({ count, items }: PendingPurchaseOrdersPanelProps) {
  return (
    <section className="rounded-card border border-border bg-surface p-8 shadow-panel">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h2 className="text-lg font-semibold text-ink">Pending procurement</h2>
          <p className="mt-1 text-sm text-muted">{count} purchase order{count === 1 ? '' : 's'} awaiting action.</p>
        </div>
        <Link className="text-sm font-medium text-brand-700 hover:underline" to="/purchase-orders">View all</Link>
      </div>
      {items.length === 0 ? (
        <p className="mt-4 text-sm text-muted">No purchase orders awaiting action.</p>
      ) : (
        <ol className="mt-5 divide-y divide-border">
          {items.map((po) => (
            <li key={po.id} className="flex items-center gap-3 py-4 first:pt-0 last:pb-0">
              <span className="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700">
                <ClipboardList aria-hidden="true" size={18} />
              </span>
              <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold text-ink">{po.poNumber}</p>
                <p className="mt-1 truncate text-sm text-muted">{po.supplierName} · {po.status.replace('_', ' ')}</p>
              </div>
              <p className="shrink-0 text-sm font-semibold tabular-nums text-ink">{po.totalAmount}</p>
            </li>
          ))}
        </ol>
      )}
    </section>
  )
}
