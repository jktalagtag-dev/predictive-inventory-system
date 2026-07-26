import { type FormEvent, useState } from 'react'
import { X } from 'lucide-react'
import { computeHistoryStartDate, defaultHistoryEndDate } from '@/features/forecasting/lib/period'
import type { CreateForecastRunPayload, PeriodGrain } from '@/features/forecasting/types/forecast'
import { Button } from '@/shared/components/Button'
import { Portal } from '@/shared/components/Portal'
import { confirmDialogOverlayClass, confirmDialogPanelClass } from '@/shared/lib/modalClasses'

type ForecastRunFormDialogProps = {
  isSaving: boolean
  onClose: () => void
  onSave: (payload: Omit<CreateForecastRunPayload, 'branchId' | 'modelCode'>) => void
}

export function ForecastRunFormDialog({ isSaving, onClose, onSave }: ForecastRunFormDialogProps) {
  const [periodGrain, setPeriodGrain] = useState<PeriodGrain>('daily')
  const [windowPeriods, setWindowPeriods] = useState(7)
  const [historyEndDate, setHistoryEndDate] = useState(defaultHistoryEndDate())

  const historyStartDate = computeHistoryStartDate(periodGrain, windowPeriods, historyEndDate)
  const isValid = windowPeriods >= 2 && windowPeriods <= 24 && historyEndDate !== ''

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onSave({ periodGrain, windowPeriods, historyStartDate, historyEndDate })
  }

  return (
    <Portal>
    <div className={confirmDialogOverlayClass} role="presentation">
      <section aria-labelledby="forecast-run-form-title" aria-modal="true" className={confirmDialogPanelClass('max-w-lg')} role="dialog">
        <div className="flex items-start justify-between gap-4">
          <div><h2 id="forecast-run-form-title" className="text-lg font-bold text-ink">Run a demand forecast</h2><p className="mt-1 text-sm text-muted">Uses the Simple Moving Average of net completed sales for every active stock product.</p></div>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>
        <form className="mt-6 grid gap-4" onSubmit={submit}>
          <label className="text-sm font-semibold text-ink">Period grain
            <select className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={periodGrain} onChange={(event) => setPeriodGrain(event.target.value as PeriodGrain)}>
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
            </select>
          </label>
          <label className="text-sm font-semibold text-ink">Window periods (2–24)
            <input className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" max={24} min={2} required type="number" value={windowPeriods} onChange={(event) => setWindowPeriods(Number(event.target.value))} />
          </label>
          <label className="text-sm font-semibold text-ink">History end date
            <input className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required type="date" value={historyEndDate} onChange={(event) => setHistoryEndDate(event.target.value)} />
          </label>
          <p className="text-sm text-muted">History start date (computed): <span className="font-medium text-ink">{historyStartDate}</span></p>
          <div className="flex justify-end gap-3 border-t border-border pt-5"><Button type="button" variant="secondary" onClick={onClose}>Cancel</Button><Button disabled={isSaving || !isValid} type="submit">{isSaving ? 'Running' : 'Run forecast'}</Button></div>
        </form>
      </section>
    </div>
    </Portal>
  )
}
