import { Receipt } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { RecentSaleItem } from '@/features/dashboard/types/dashboard'

export function RecentSalesPanel({ sales }: { sales: RecentSaleItem[] }) {
  return (
    <section className="rounded-card border border-border bg-surface p-8 shadow-panel">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h2 className="text-lg font-semibold text-ink">Recent sales</h2>
          <p className="mt-1 text-sm text-muted">Latest completed transactions for this branch.</p>
        </div>
        <Link className="text-sm font-medium text-brand-700 hover:underline" to="/sales">View all</Link>
      </div>
      {sales.length === 0 ? (
        <p className="mt-4 text-sm text-muted">No completed sales yet.</p>
      ) : (
        <ol className="mt-5 divide-y divide-border">
          {sales.map((sale) => (
            <li key={sale.id} className="flex items-center gap-3 py-4 first:pt-0 last:pb-0">
              <span className="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700">
                <Receipt aria-hidden="true" size={18} />
              </span>
              <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold text-ink">{sale.saleNumber}</p>
                <p className="mt-1 truncate text-sm text-muted">{sale.cashierName ?? 'Unknown cashier'}</p>
              </div>
              <div className="shrink-0 text-right">
                <p className="text-sm font-semibold tabular-nums text-ink">{sale.totalAmount}</p>
                <time className="text-xs text-muted">{sale.soldAt ? new Date(sale.soldAt).toLocaleTimeString() : '—'}</time>
              </div>
            </li>
          ))}
        </ol>
      )}
    </section>
  )
}
