import { PanelRightOpen } from 'lucide-react'
import type { Sale } from '@/features/sales/types/sale'
import { SaleStatusBadge } from '@/features/sales/components/SaleStatusBadge'
import { Button } from '@/shared/components/Button'

export function SaleTable({ sales, onView }: { sales: Sale[]; onView: (sale: Sale) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[750px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr><th className="px-5 py-3">Sale number</th><th className="px-5 py-3">Cashier</th><th className="px-5 py-3">Status</th><th className="px-5 py-3">Sold at</th><th className="px-5 py-3 text-right">Lines</th><th className="px-5 py-3 text-right">Total</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {sales.map((sale) => (
              <tr key={sale.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4 font-mono text-xs font-semibold text-ink">{sale.saleNumber}</td>
                <td className="px-5 py-4 text-muted">{sale.cashierName ?? '—'}</td>
                <td className="px-5 py-4"><SaleStatusBadge status={sale.status} /></td>
                <td className="px-5 py-4 text-muted">{sale.soldAt ? new Date(sale.soldAt).toLocaleString() : '—'}</td>
                <td className="px-5 py-4 text-right tabular-nums text-ink">{sale.lineCount ?? '—'}</td>
                <td className="px-5 py-4 text-right tabular-nums font-semibold text-ink">{sale.totalAmount}</td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`View ${sale.saleNumber}`} size="icon" variant="ghost" onClick={() => onView(sale)}><PanelRightOpen aria-hidden="true" size={17} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
