import { useState } from 'react'
import { PencilLine, X } from 'lucide-react'
import { ColdStartBadge } from '@/features/forecasting/components/ColdStartBadge'
import { ManualPlanDialog } from '@/features/forecasting/components/ManualPlanDialog'
import type { ForecastRun, ForecastRunItem } from '@/features/forecasting/types/forecast'
import { Button } from '@/shared/components/Button'

type ForecastRunDetailsDrawerProps = {
  run: ForecastRun
  canOverride: boolean
  isSaving: boolean
  onClose: () => void
  onManualPlan: (item: ForecastRunItem, manualQuantity: string, reason: string, expiresAt: string) => void
}

export function ForecastRunDetailsDrawer({ run, canOverride, isSaving, onClose, onManualPlan }: ForecastRunDetailsDrawerProps) {
  const [overrideItem, setOverrideItem] = useState<ForecastRunItem | null>(null)

  return (
    <div className="fixed inset-0 z-40 bg-slate-950/30" role="presentation" onMouseDown={onClose}>
      <aside aria-labelledby="forecast-run-details-title" aria-modal="true" className="ml-auto flex h-full w-full max-w-3xl flex-col border-l border-border bg-surface shadow-panel" role="dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="flex items-start justify-between gap-4 border-b border-border p-6">
          <div>
            <p className="font-mono text-xs text-muted">Run #{run.id} · {run.modelVersion}</p>
            <h2 id="forecast-run-details-title" className="mt-1 text-xl font-bold tracking-tight text-ink capitalize">{run.periodGrain} SMA, {run.windowPeriods} periods</h2>
            <p className="mt-1 text-sm text-muted">History {run.historyStartDate} – {run.historyEndDate} · Cutoff {run.dataCutoffAt ? new Date(run.dataCutoffAt).toLocaleString() : '—'}</p>
          </div>
          <Button aria-label="Close forecast run details" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </header>
        <div className="flex-1 overflow-y-auto p-6">
          <div className="overflow-x-auto rounded-lg border border-border">
            <table className="w-full min-w-[700px] text-sm">
              <thead className="bg-subtle text-left text-xs font-semibold text-muted">
                <tr><th className="px-3 py-2">Product</th><th className="px-3 py-2 text-right">Demand total</th><th className="px-3 py-2 text-right">Forecast / period</th><th className="px-3 py-2">Status</th><th className="px-3 py-2 text-right">Actions</th></tr>
              </thead>
              <tbody className="divide-y divide-border">
                {run.items.map((item) => (
                  <tr key={item.productId}>
                    <td className="px-3 py-2"><p className="font-medium text-ink">{item.productName}</p><p className="text-xs text-muted">{item.productSku}</p></td>
                    <td className="px-3 py-2 text-right tabular-nums">{item.demandTotal}</td>
                    <td className="px-3 py-2 text-right tabular-nums font-semibold">
                      {item.coldStartStatus === 'manual_override' ? item.manualQuantity : (item.forecastQuantity ?? '—')}
                    </td>
                    <td className="px-3 py-2"><ColdStartBadge status={item.coldStartStatus} /></td>
                    <td className="px-3 py-2 text-right">
                      {canOverride ? <Button aria-label={`Manual plan for ${item.productName}`} size="icon" variant="ghost" onClick={() => setOverrideItem(item)}><PencilLine aria-hidden="true" size={16} /></Button> : null}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </aside>
      {overrideItem ? (
        <ManualPlanDialog
          isSaving={isSaving}
          item={overrideItem}
          onClose={() => setOverrideItem(null)}
          onSave={(quantity, reason, expiresAt) => { onManualPlan(overrideItem, quantity, reason, expiresAt); setOverrideItem(null) }}
        />
      ) : null}
    </div>
  )
}
