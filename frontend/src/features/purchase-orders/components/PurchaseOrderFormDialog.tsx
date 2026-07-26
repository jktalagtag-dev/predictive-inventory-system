import { type FormEvent, useState } from 'react'
import { Plus, Trash2, X } from 'lucide-react'
import type { UnitOption } from '@/features/products/types/product'
import type { SupplierOption } from '@/features/suppliers/types/supplier'
import type { PurchaseOrderFormValues, PurchaseOrderLineInput } from '@/features/purchase-orders/types/purchaseOrder'
import { Button } from '@/shared/components/Button'
import { cn } from '@/shared/lib/cn'
import { modalOverlayClass, modalPanelClass, sheetBodyClass, sheetFooterClass, sheetHeaderClass } from '@/shared/lib/modalClasses'
import { Portal } from '@/shared/components/Portal'

type ProductOption = { id: string; sku: string; name: string }

type PurchaseOrderFormDialogProps = {
  supplierOptions: SupplierOption[]
  productOptions: ProductOption[]
  unitOptions: UnitOption[]
  isSaving: boolean
  onClose: () => void
  onSave: (values: PurchaseOrderFormValues) => void
}

const emptyLine: PurchaseOrderLineInput = { productId: '', unitId: '', orderedQuantity: '', unitCost: '', taxRate: '12', discountAmount: '0' }

export function PurchaseOrderFormDialog({ supplierOptions, productOptions, unitOptions, isSaving, onClose, onSave }: PurchaseOrderFormDialogProps) {
  const [values, setValues] = useState<PurchaseOrderFormValues>({
    supplierId: '', currencyCode: 'PHP', expectedReceiptAt: '', supplierReference: '', notes: '', lines: [{ ...emptyLine }],
  })

  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); onSave(values) }

  const updateLine = (index: number, patch: Partial<PurchaseOrderLineInput>) => {
    setValues((state) => ({ ...state, lines: state.lines.map((line, i) => (i === index ? { ...line, ...patch } : line)) }))
  }

  const addLine = () => setValues((state) => ({ ...state, lines: [...state.lines, { ...emptyLine }] }))
  const removeLine = (index: number) => setValues((state) => ({ ...state, lines: state.lines.filter((_, i) => i !== index) }))

  const isValid = values.supplierId !== '' && values.lines.length > 0
    && values.lines.every((line) => line.productId && line.unitId && line.orderedQuantity && line.unitCost)

  return (
    <Portal>
    <div className={modalOverlayClass} role="presentation">
      <section aria-labelledby="po-form-title" aria-modal="true" className={modalPanelClass('sm:max-w-4xl')} role="dialog">
        <div className={sheetHeaderClass}><div><h2 id="po-form-title" className="text-lg font-bold text-ink">Create purchase order</h2><p className="mt-1 text-sm text-muted">Totals are calculated by the server once the draft is created.</p></div><Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button></div>
        <form className="flex min-h-0 flex-1 flex-col" onSubmit={submit}>
          <div className={cn(sheetBodyClass, 'space-y-5')}>
            <div className="grid gap-4 sm:grid-cols-3">
              <label className="text-sm font-semibold text-ink sm:col-span-1">Supplier
                <select className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.supplierId} onChange={(event) => {
                  const supplier = supplierOptions.find((option) => option.id === event.target.value)
                  setValues((state) => ({ ...state, supplierId: event.target.value, currencyCode: supplier?.defaultCurrencyCode ?? state.currencyCode }))
                }}>
                  <option value="" disabled>Select a supplier</option>
                  {supplierOptions.map((option) => <option key={option.id} value={option.id}>{option.legalName}</option>)}
                </select>
              </label>
              <label className="text-sm font-semibold text-ink">Currency<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm uppercase outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" maxLength={3} required value={values.currencyCode} onChange={(event) => setValues((state) => ({ ...state, currencyCode: event.target.value.toUpperCase() }))} /></label>
              <label className="text-sm font-semibold text-ink">Expected receipt<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" type="date" value={values.expectedReceiptAt} onChange={(event) => setValues((state) => ({ ...state, expectedReceiptAt: event.target.value }))} /></label>
            </div>

            <div>
              <div className="flex items-center justify-between">
                <h3 className="text-sm font-semibold text-ink">Lines</h3>
                <Button size="icon" type="button" variant="ghost" onClick={addLine}><Plus aria-hidden="true" size={16} /><span className="sr-only">Add line</span></Button>
              </div>
              <div className="mt-2 space-y-3">
                {values.lines.map((line, index) => (
                  <div className="flex flex-col gap-2 rounded-xl border border-border p-3 sm:grid sm:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_90px_100px_80px_80px_36px] sm:items-end" key={index}>
                    <label className="text-xs font-semibold text-muted">Product
                      <select className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" required value={line.productId} onChange={(event) => updateLine(index, { productId: event.target.value })}>
                        <option value="" disabled>Select</option>
                        {productOptions.map((option) => <option key={option.id} value={option.id}>{option.sku} — {option.name}</option>)}
                      </select>
                    </label>
                    <label className="text-xs font-semibold text-muted">Unit
                      <select className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" required value={line.unitId} onChange={(event) => updateLine(index, { unitId: event.target.value })}>
                        <option value="" disabled>Select</option>
                        {unitOptions.map((option) => <option key={option.id} value={option.id}>{option.symbol}</option>)}
                      </select>
                    </label>
                    <label className="text-xs font-semibold text-muted">Qty
                      <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" min="0" required step="0.0001" type="number" value={line.orderedQuantity} onChange={(event) => updateLine(index, { orderedQuantity: event.target.value })} />
                    </label>
                    <label className="text-xs font-semibold text-muted">Unit cost
                      <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" min="0" required step="0.0001" type="number" value={line.unitCost} onChange={(event) => updateLine(index, { unitCost: event.target.value })} />
                    </label>
                    <label className="text-xs font-semibold text-muted">Tax %
                      <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" min="0" step="0.0001" type="number" value={line.taxRate} onChange={(event) => updateLine(index, { taxRate: event.target.value })} />
                    </label>
                    <label className="text-xs font-semibold text-muted">Discount
                      <input className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600" min="0" step="0.0001" type="number" value={line.discountAmount} onChange={(event) => updateLine(index, { discountAmount: event.target.value })} />
                    </label>
                    <Button aria-label="Remove line" className="self-end sm:mb-0.5" disabled={values.lines.length === 1} size="icon" type="button" variant="ghost" onClick={() => removeLine(index)}><Trash2 aria-hidden="true" size={16} /></Button>
                  </div>
                ))}
              </div>
            </div>

            <label className="block text-sm font-semibold text-ink">Notes
              <textarea className="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" rows={2} value={values.notes} onChange={(event) => setValues((state) => ({ ...state, notes: event.target.value }))} />
            </label>
          </div>

          <div className={sheetFooterClass}>
            <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
            <Button disabled={isSaving || !isValid} type="submit">{isSaving ? 'Saving' : 'Create draft'}</Button>
          </div>
        </form>
      </section>
    </div>
    </Portal>
  )
}
