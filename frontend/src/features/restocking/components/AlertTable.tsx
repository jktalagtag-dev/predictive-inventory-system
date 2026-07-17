import { PanelRightOpen } from 'lucide-react'
import { AlertStatusBadge } from '@/features/restocking/components/AlertStatusBadge'
import { SeverityBadge } from '@/features/restocking/components/SeverityBadge'
import type { RestockingAlert } from '@/features/restocking/types/restocking'
import { Button } from '@/shared/components/Button'

export function AlertTable({ alerts, onView }: { alerts: RestockingAlert[]; onView: (alert: RestockingAlert) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[800px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr><th className="px-5 py-3">Product</th><th className="px-5 py-3">Severity</th><th className="px-5 py-3">Status</th><th className="px-5 py-3 text-right">Available</th><th className="px-5 py-3 text-right">Reorder point</th><th className="px-5 py-3 text-right">Recommended order</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {alerts.map((alert) => (
              <tr key={alert.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4"><p className="font-medium text-ink">{alert.productName ?? '—'}</p><p className="font-mono text-xs text-muted">{alert.productSku}</p></td>
                <td className="px-5 py-4"><SeverityBadge severity={alert.severity} /></td>
                <td className="px-5 py-4"><AlertStatusBadge status={alert.status} /></td>
                <td className="px-5 py-4 text-right tabular-nums">{alert.availableQuantitySnapshot}</td>
                <td className="px-5 py-4 text-right tabular-nums">{alert.reorderPointSnapshot}</td>
                <td className="px-5 py-4 text-right tabular-nums font-semibold text-ink">{alert.recommendedOrderQuantity ?? '—'}</td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`View alert for ${alert.productName}`} size="icon" variant="ghost" onClick={() => onView(alert)}><PanelRightOpen aria-hidden="true" size={17} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
