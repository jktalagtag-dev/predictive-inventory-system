import { AlertTriangle } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { LowStockItem } from '@/features/dashboard/types/dashboard'

type LowStockTableProps = { items: LowStockItem[] }

const severityLabels: Record<LowStockItem['severity'], string> = {
  critical: 'Critical',
  high: 'High',
  medium: 'Medium',
  low: 'Low',
}

const severityClasses: Record<LowStockItem['severity'], string> = {
  critical: 'bg-red-50 text-red-700',
  high: 'bg-orange-50 text-orange-700',
  medium: 'bg-amber-50 text-amber-700',
  low: 'bg-slate-100 text-slate-700',
}

export function LowStockTable({ items }: LowStockTableProps) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="flex items-center justify-between gap-4 border-b border-border p-5">
        <div>
          <h2 className="text-base font-semibold text-ink">Stock exceptions</h2>
          <p className="mt-1 text-sm text-muted">Active restocking alerts requiring attention.</p>
        </div>
        <Link className="text-sm font-medium text-brand-700 hover:underline" to="/restocking">View all</Link>
      </div>
      {items.length === 0 ? (
        <p className="p-5 text-sm text-muted">No active restocking alerts for this branch.</p>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full min-w-[600px] text-sm">
            <thead className="bg-subtle text-left text-xs font-semibold text-muted">
              <tr>
                <th className="px-5 py-3">Product</th>
                <th className="px-5 py-3">SKU</th>
                <th className="px-5 py-3 text-right">Available</th>
                <th className="px-5 py-3 text-right">Reorder point</th>
                <th className="px-5 py-3">Severity</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {items.map((item) => (
                <tr key={item.id} className="hover:bg-subtle/70">
                  <td className="px-5 py-4 font-medium text-ink">{item.productName}</td>
                  <td className="px-5 py-4 font-mono text-xs text-muted">{item.productSku}</td>
                  <td className="px-5 py-4 text-right font-semibold tabular-nums text-ink">{item.availableQuantity}</td>
                  <td className="px-5 py-4 text-right tabular-nums text-muted">{item.reorderPointQuantity}</td>
                  <td className="px-5 py-4">
                    <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ${severityClasses[item.severity]}`}>
                      <AlertTriangle aria-hidden="true" size={12} />
                      {severityLabels[item.severity]}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </section>
  )
}
