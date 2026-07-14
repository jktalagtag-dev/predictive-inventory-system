import { AlertTriangle } from 'lucide-react'
import type { StockAlert } from '@/features/dashboard/types/dashboard'

type StockAlertTableProps = { alerts: StockAlert[] }

export function StockAlertTable({ alerts }: StockAlertTableProps) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="flex items-center justify-between gap-4 border-b border-border p-5">
        <div>
          <h2 className="text-base font-semibold text-ink">Stock exceptions</h2>
          <p className="mt-1 text-sm text-muted">Products requiring replenishment attention.</p>
        </div>
        <AlertTriangle aria-hidden="true" className="text-amber-700" size={20} />
      </div>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[600px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr>
              <th className="px-5 py-3">Product</th>
              <th className="px-5 py-3">SKU</th>
              <th className="px-5 py-3 text-right">Available</th>
              <th className="px-5 py-3 text-right">ROP</th>
              <th className="px-5 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {alerts.map((alert) => (
              <tr key={alert.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4 font-medium text-ink">{alert.productName}</td>
                <td className="px-5 py-4 font-mono text-xs text-muted">{alert.sku}</td>
                <td className="px-5 py-4 text-right font-semibold tabular-nums text-ink">{alert.availableQuantity}</td>
                <td className="px-5 py-4 text-right tabular-nums text-muted">{alert.reorderPoint}</td>
                <td className="px-5 py-4">
                  <span className={alert.status === 'critical'
                    ? 'inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700'
                    : 'inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700'}
                  >
                    {alert.status === 'critical' ? 'Critical stock' : 'Low stock'}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
