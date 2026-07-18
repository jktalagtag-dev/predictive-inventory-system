import { type FormEvent, useState } from 'react'
import { RefreshCw, X } from 'lucide-react'
import { useEoqHistory } from '@/features/restocking/hooks/useRestocking'
import type { ReorderPolicy } from '@/features/restocking/types/restocking'
import { Button } from '@/shared/components/Button'
import { drawerPanelClass } from '@/shared/lib/modalClasses'

type ReorderPolicyDetailsDrawerProps = {
  policy: ReorderPolicy
  canCalculate: boolean
  canCalculateEoq: boolean
  isActing: boolean
  onClose: () => void
  onRecalculateRop: () => void
  onCalculateEoq: (annualDemandQuantity: string, orderingCost: string, annualHoldingCostPerUnit: string) => void
}

export function ReorderPolicyDetailsDrawer({ policy, canCalculate, canCalculateEoq, isActing, onClose, onRecalculateRop, onCalculateEoq }: ReorderPolicyDetailsDrawerProps) {
  const eoqHistoryQuery = useEoqHistory(policy.id)
  const [annualDemand, setAnnualDemand] = useState('')
  const [orderingCost, setOrderingCost] = useState('')
  const [holdingCost, setHoldingCost] = useState('')

  const submitEoq = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onCalculateEoq(annualDemand, orderingCost, holdingCost)
  }

  return (
    <div className="fixed inset-0 z-40 bg-slate-950/30" role="presentation" onMouseDown={onClose}>
      <aside aria-labelledby="rop-details-title" aria-modal="true" className={drawerPanelClass()} role="dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="flex items-start justify-between gap-4 border-b border-border p-4 sm:p-6">
          <div>
            <p className="font-mono text-xs text-muted">Reorder policy #{policy.id}</p>
            <h2 id="rop-details-title" className="mt-1 text-xl font-bold tracking-tight text-ink">{policy.productName}</h2>
            <p className="mt-1 text-sm text-muted">{policy.productSku}</p>
          </div>
          <Button aria-label="Close reorder policy details" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </header>
        <div className="flex-1 space-y-6 overflow-y-auto p-4 sm:p-6">
          <section className="grid grid-cols-1 gap-4 rounded-xl border border-border p-4 text-sm sm:grid-cols-2">
            <div><p className="text-muted">Safety stock</p><p className="font-semibold text-ink">{policy.safetyStockQuantity} ({policy.safetyStockBasis.replace('_', ' ')})</p></div>
            <div><p className="text-muted">Lead time</p><p className="font-semibold text-ink">{policy.leadTimeDaysOverride ?? '—'} days ({policy.leadTimeBasis.replace('_', ' ')})</p></div>
            <div><p className="text-muted">Reorder point</p><p className="font-semibold text-ink">{policy.reorderPointQuantity ?? 'Not calculated'}</p></div>
            <div><p className="text-muted">Calculated at</p><p className="font-semibold text-ink">{policy.ropCalculatedAt ? new Date(policy.ropCalculatedAt).toLocaleString() : '—'}</p></div>
          </section>

          {canCalculate ? (
            <Button disabled={isActing} onClick={onRecalculateRop}><RefreshCw aria-hidden="true" size={16} /> Recalculate reorder point</Button>
          ) : null}

          {canCalculateEoq ? (
            <section className="space-y-3 rounded-xl border border-border p-4">
              <h3 className="text-sm font-semibold text-ink">Calculate EOQ</h3>
              <form className="grid grid-cols-1 gap-3 sm:grid-cols-3" onSubmit={submitEoq}>
                <label className="text-xs font-semibold text-muted">Annual demand
                  <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600/30" min="0" required step="0.01" type="number" value={annualDemand} onChange={(event) => setAnnualDemand(event.target.value)} />
                </label>
                <label className="text-xs font-semibold text-muted">Ordering cost
                  <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600/30" min="0" required step="0.01" type="number" value={orderingCost} onChange={(event) => setOrderingCost(event.target.value)} />
                </label>
                <label className="text-xs font-semibold text-muted">Holding cost / unit
                  <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600/30" min="0" required step="0.01" type="number" value={holdingCost} onChange={(event) => setHoldingCost(event.target.value)} />
                </label>
                <div className="sm:col-span-3"><Button disabled={isActing} type="submit">Calculate EOQ</Button></div>
              </form>

              {eoqHistoryQuery.data && eoqHistoryQuery.data.length > 0 ? (
                <ul className="space-y-1.5 text-sm">
                  {eoqHistoryQuery.data.map((calc) => (
                    <li key={calc.id} className="flex justify-between rounded-xl border border-border px-3 py-2">
                      <span className="text-muted">{calc.calculatedAt ? new Date(calc.calculatedAt).toLocaleString() : '—'}</span>
                      <span className="font-semibold text-ink">{calc.recommendedOrderQuantity ?? '—'} units</span>
                    </li>
                  ))}
                </ul>
              ) : <p className="text-sm text-muted">No EOQ calculations yet.</p>}
            </section>
          ) : null}
        </div>
      </aside>
    </div>
  )
}
