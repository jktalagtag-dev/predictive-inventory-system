import { type FormEvent, useState } from 'react'
import { Plus, Trash2, X } from 'lucide-react'
import { ADJUSTMENT_REASON_CODES, type AdjustmentFormValues, type AdjustmentLineInput } from '@/features/inventory/types/inventory'
import { Button } from '@/shared/components/Button'

type ProductOption = { id: string; sku: string; name: string }

type AdjustmentFormDialogProps = {
  productOptions: ProductOption[]
  isSaving: boolean
  onClose: () => void
  onSave: (values: AdjustmentFormValues) => void
}

const emptyLine: AdjustmentLineInput = { productId: '', quantityDelta: '', unitCost: '', notes: '' }

const reasonLabels: Record<(typeof ADJUSTMENT_REASON_CODES)[number], string> = {
  damage: 'Damage',
  count_correction: 'Count correction',
  theft: 'Theft',
  expiry: 'Expiry',
  other: 'Other',
}

export function AdjustmentFormDialog({ productOptions, isSaving, onClose, onSave }: AdjustmentFormDialogProps) {
  const [values, setValues] = useState<AdjustmentFormValues>({
    reasonCode: 'count_correction', reasonNote: '', effectiveAt: new Date().toISOString().slice(0, 10), lines: [{ ...emptyLine }],
  })

  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); onSave({ ...values, effectiveAt: new Date(values.effectiveAt).toISOString() }) }

  const updateLine = (index: number, patch: Partial<AdjustmentLineInput>) => {
    setValues((state) => ({ ...state, lines: state.lines.map((line, i) => (i === index ? { ...line, ...patch } : line)) }))
  }

  const addLine = () => setValues((state) => ({ ...state, lines: [...state.lines, { ...emptyLine }] }))
  const removeLine = (index: number) => setValues((state) => ({ ...state, lines: state.lines.filter((_, i) => i !== index) }))

  const isValid = values.lines.length > 0 && values.lines.every((line) => line.productId && line.quantityDelta && Number(line.quantityDelta) !== 0)

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation">
      <section aria-labelledby="adj-form-title" aria-modal="true" className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-border bg-surface p-6 shadow-panel" role="dialog">
        <div className="flex items-start justify-between gap-4"><div><h2 id="adj-form-title" className="text-lg font-bold text-ink">Create inventory adjustment</h2><p className="mt-1 text-sm text-muted">Before/after quantities are calculated by the server once the draft is created.</p></div><Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button></div>
        <form className="mt-6 space-y-5" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <label className="text-sm font-semibold text-ink">Reason
              <select className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.reasonCode} onChange={(event) => setValues((state) => ({ ...state, reasonCode: event.target.value as AdjustmentFormValues['reasonCode'] }))}>
                {ADJUSTMENT_REASON_CODES.map((code) => <option key={code} value={code}>{reasonLabels[code]}</option>)}
              </select>
            </label>
            <label className="text-sm font-semibold text-ink">Effective date<input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required type="date" value={values.effectiveAt} onChange={(event) => setValues((state) => ({ ...state, effectiveAt: event.target.value }))} /></label>
          </div>

          <label className="block text-sm font-semibold text-ink">Notes<textarea className="mt-2 w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" rows={2} value={values.reasonNote} onChange={(event) => setValues((state) => ({ ...state, reasonNote: event.target.value }))} /></label>

          <div>
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-semibold text-ink">Lines</h3>
              <Button size="icon" type="button" variant="ghost" onClick={addLine}><Plus aria-hidden="true" size={16} /><span className="sr-only">Add line</span></Button>
            </div>
            <div className="mt-2 space-y-3">
              {values.lines.map((line, index) => (
                <div className="grid grid-cols-[minmax(0,1.5fr)_110px_110px_minmax(0,1fr)_36px] items-end gap-2 rounded-lg border border-border p-3" key={index}>
                  <label className="text-xs font-semibold text-muted">Product
                    <select className="mt-1 h-9 w-full rounded-md border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" required value={line.productId} onChange={(event) => updateLine(index, { productId: event.target.value })}>
                      <option value="" disabled>Select</option>
                      {productOptions.map((option) => <option key={option.id} value={option.id}>{option.sku} — {option.name}</option>)}
                    </select>
                  </label>
                  <label className="text-xs font-semibold text-muted">Delta
                    <input className="mt-1 h-9 w-full rounded-md border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" placeholder="±0.0000" required step="0.0001" type="number" value={line.quantityDelta} onChange={(event) => updateLine(index, { quantityDelta: event.target.value })} />
                  </label>
                  <label className="text-xs font-semibold text-muted">Unit cost
                    <input className="mt-1 h-9 w-full rounded-md border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" min="0" step="0.0001" type="number" value={line.unitCost} onChange={(event) => updateLine(index, { unitCost: event.target.value })} />
                  </label>
                  <label className="text-xs font-semibold text-muted">Line note
                    <input className="mt-1 h-9 w-full rounded-md border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" value={line.notes} onChange={(event) => updateLine(index, { notes: event.target.value })} />
                  </label>
                  <Button aria-label="Remove line" className="mb-0.5" disabled={values.lines.length === 1} size="icon" type="button" variant="ghost" onClick={() => removeLine(index)}><Trash2 aria-hidden="true" size={16} /></Button>
                </div>
              ))}
            </div>
          </div>

          <div className="flex justify-end gap-3 border-t border-border pt-5">
            <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
            <Button disabled={isSaving || !isValid} type="submit">{isSaving ? 'Saving' : 'Create draft'}</Button>
          </div>
        </form>
      </section>
    </div>
  )
}
