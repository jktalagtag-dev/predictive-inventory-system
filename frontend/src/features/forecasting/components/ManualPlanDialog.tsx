import { type FormEvent, useState } from 'react'
import { X } from 'lucide-react'
import type { ForecastRunItem } from '@/features/forecasting/types/forecast'
import { Button } from '@/shared/components/Button'
import { Portal } from '@/shared/components/Portal'
import { confirmDialogOverlayClass, confirmDialogPanelClass } from '@/shared/lib/modalClasses'

type ManualPlanDialogProps = {
  item: ForecastRunItem
  isSaving: boolean
  onClose: () => void
  onSave: (manualQuantity: string, reason: string, expiresAt: string) => void
}

function defaultExpiry(): string {
  const date = new Date()
  date.setDate(date.getDate() + 30)
  return date.toISOString().slice(0, 10)
}

export function ManualPlanDialog({ item, isSaving, onClose, onSave }: ManualPlanDialogProps) {
  const [quantity, setQuantity] = useState(item.manualQuantity ?? item.forecastQuantity ?? '')
  const [reason, setReason] = useState(item.manualReason ?? '')
  const [expiresAt, setExpiresAt] = useState(defaultExpiry())

  const isValid = quantity !== '' && Number(quantity) >= 0 && reason.trim() !== '' && expiresAt !== ''

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onSave(quantity, reason, new Date(`${expiresAt}T00:00:00`).toISOString())
  }

  return (
    <Portal>
    <div className={confirmDialogOverlayClass} role="presentation" onMouseDown={(event) => event.stopPropagation()}>
      <section aria-labelledby="manual-plan-title" aria-modal="true" className={confirmDialogPanelClass('max-w-md')} role="dialog">
        <div className="flex items-start justify-between gap-4">
          <div><h2 id="manual-plan-title" className="text-lg font-bold text-ink">Manual plan override</h2><p className="mt-1 text-sm text-muted">{item.productName} ({item.productSku}). This is audited and does not rewrite the original SMA output.</p></div>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>
        <form className="mt-4 space-y-4" onSubmit={submit}>
          <label className="block text-sm font-semibold text-ink">Manual quantity per period
            <input className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" min="0" required step="0.0001" type="number" value={quantity} onChange={(event) => setQuantity(event.target.value)} />
          </label>
          <label className="block text-sm font-semibold text-ink">Reason
            <textarea className="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required rows={3} value={reason} onChange={(event) => setReason(event.target.value)} />
          </label>
          <label className="block text-sm font-semibold text-ink">Expires
            <input className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required type="date" value={expiresAt} onChange={(event) => setExpiresAt(event.target.value)} />
          </label>
          <div className="flex justify-end gap-3 border-t border-border pt-5"><Button type="button" variant="secondary" onClick={onClose}>Cancel</Button><Button disabled={isSaving || !isValid} type="submit">{isSaving ? 'Saving' : 'Save override'}</Button></div>
        </form>
      </section>
    </div>
    </Portal>
  )
}
