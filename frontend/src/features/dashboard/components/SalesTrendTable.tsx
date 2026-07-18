import type { SalesTrendPoint } from '@/features/dashboard/types/dashboard'

/**
 * A plain accessible table rather than a chart. CLAUDE.md section 30
 * requires every visualization to have an accessible tabular
 * equivalent — since no visual chart is built yet, the table itself is
 * the primary (and only) representation, so it can never fall out of
 * sync with a chart it would otherwise duplicate.
 */
export function SalesTrendTable({ points }: { points: SalesTrendPoint[] }) {
  const nonZeroDays = points.filter((point) => point.saleCount > 0).length

  return (
    <section className="rounded-card border border-border bg-surface p-8 shadow-panel">
      <div>
        <h2 className="text-lg font-semibold text-ink">Sales trend</h2>
        <p className="mt-1 text-sm text-muted">{nonZeroDays} day{nonZeroDays === 1 ? '' : 's'} with completed sales in range.</p>
      </div>
      <div className="mt-4 max-h-64 overflow-y-auto">
        <table className="w-full text-sm">
          <thead className="sticky top-0 bg-surface text-left text-xs font-semibold text-muted">
            <tr>
              <th className="py-2 pr-3">Date</th>
              <th className="py-2 pr-3 text-right">Sales</th>
              <th className="py-2 text-right">Total</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {points.map((point) => (
              <tr key={point.date}>
                <td className="py-2 pr-3 tabular-nums text-ink">{point.date}</td>
                <td className="py-2 pr-3 text-right tabular-nums text-muted">{point.saleCount}</td>
                <td className="py-2 text-right font-medium tabular-nums text-ink">{point.totalAmount}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
