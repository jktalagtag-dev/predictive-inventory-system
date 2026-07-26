import { AlertTriangle } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { LowStockItem } from '@/features/dashboard/types/dashboard'
import { TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

type LowStockTableProps = { items: LowStockItem[] }

const severityLabels: Record<LowStockItem['severity'], string> = {
  critical: 'Critical',
  high: 'High',
  medium: 'Medium',
  low: 'Low',
}

// Red-family shades for both alert-worthy tiers per DESIGN_SYSTEM.md's
// "Red only for alerts" rule; medium is caution (warning), low is
// merely informational (neutral), not treated as an alert color.
const severityClasses: Record<LowStockItem['severity'], string> = {
  critical: 'bg-danger/15 text-danger-text',
  high: 'bg-danger/10 text-danger-text',
  medium: 'bg-warning/10 text-warning-text',
  low: 'bg-subtle text-muted',
}

const severityPill = (severity: LowStockItem['severity']) => (
  <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ${severityClasses[severity]}`}>
    <AlertTriangle aria-hidden="true" size={14} />
    {severityLabels[severity]}
  </span>
)

export function LowStockTable({ items }: LowStockTableProps) {
  return (
    <section className="overflow-hidden rounded-card border border-border bg-surface shadow-panel">
      <div className="flex items-center justify-between gap-4 border-b border-border p-6 sm:p-8">
        <div>
          <h2 className="text-lg font-semibold text-ink">Stock exceptions</h2>
          <p className="mt-1 text-sm text-muted">Active restocking alerts requiring attention.</p>
        </div>
        <Link className="shrink-0 text-sm font-medium text-brand-700 hover:underline" to="/restocking">View all</Link>
      </div>

      {/* Mobile: stacked rows */}
      <div className="md:hidden">
        {items.length === 0 ? (
          <p className="p-6 text-center text-sm text-muted">No active restocking alerts for this branch.</p>
        ) : (
          <ul className="divide-y divide-border">
            {items.map((item) => (
              <li key={item.id} className="flex items-start justify-between gap-3 p-4">
                <div className="min-w-0">
                  <p className="truncate font-medium text-ink">{item.productName}</p>
                  <p className="truncate font-mono text-xs text-muted">{item.productSku}</p>
                  <p className="mt-1 text-xs text-muted">Available <span className="font-semibold text-ink">{item.availableQuantity}</span> · Reorder point {item.reorderPointQuantity}</p>
                </div>
                <div className="shrink-0">{severityPill(item.severity)}</div>
              </li>
            ))}
          </ul>
        )}
      </div>

      {/* Desktop: table */}
      <div className="hidden overflow-x-auto md:block">
        <table className="w-full text-sm" style={{ minWidth: 600 }}>
          <TableHead>
            <tr>
              <TableHeaderCell>Product</TableHeaderCell>
              <TableHeaderCell>SKU</TableHeaderCell>
              <TableHeaderCell align="right">Available</TableHeaderCell>
              <TableHeaderCell align="right">Reorder point</TableHeaderCell>
              <TableHeaderCell>Severity</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {items.length === 0 ? (
              <TableEmptyState colSpan={5}>No active restocking alerts for this branch.</TableEmptyState>
            ) : (
              items.map((item) => (
                <TableRow key={item.id}>
                  <TableCell className="font-medium text-ink">{item.productName}</TableCell>
                  <TableCell className="font-mono text-xs text-muted">{item.productSku}</TableCell>
                  <TableCell align="right" className="font-semibold text-ink">{item.availableQuantity}</TableCell>
                  <TableCell align="right" className="text-muted">{item.reorderPointQuantity}</TableCell>
                  <TableCell>{severityPill(item.severity)}</TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </table>
      </div>
    </section>
  )
}
