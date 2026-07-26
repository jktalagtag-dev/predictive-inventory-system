import { type FormEvent, useState } from 'react'
import { X } from 'lucide-react'
import type { CreateReorderPolicyPayload, LeadTimeBasis, SafetyStockBasis } from '@/features/restocking/types/restocking'
import { Button } from '@/shared/components/Button'
import { Portal } from '@/shared/components/Portal'
import { confirmDialogOverlayClass, confirmDialogPanelClass } from '@/shared/lib/modalClasses'

type ProductOption = { id: string; sku: string; name: string }

type ReorderPolicyFormDialogProps = {
  productOptions: ProductOption[]
  isSaving: boolean
  onClose: () => void
  onSave: (payload: Omit<CreateReorderPolicyPayload, 'branchId'>) => void
}

export function ReorderPolicyFormDialog({ productOptions, isSaving, onClose, onSave }: ReorderPolicyFormDialogProps) {
  const [productId, setProductId] = useState('')
  const [safetyStockQuantity, setSafetyStockQuantity] = useState('0')
  const [safetyStockBasis, setSafetyStockBasis] = useState<SafetyStockBasis>('policy_minimum')
  const [leadTimeDaysOverride, setLeadTimeDaysOverride] = useState('')
  const [leadTimeBasis, setLeadTimeBasis] = useState<LeadTimeBasis>('product_default')

  const isValid = productId !== '' && safetyStockQuantity !== ''

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onSave({
      productId,
      safetyStockQuantity,
      safetyStockBasis,
      leadTimeDaysOverride: leadTimeDaysOverride || undefined,
      leadTimeBasis: leadTimeDaysOverride ? 'override' : leadTimeBasis,
    })
  }

  return (
    <Portal>
    <div className={confirmDialogOverlayClass} role="presentation">
      <section aria-labelledby="reorder-policy-form-title" aria-modal="true" className={confirmDialogPanelClass('max-w-lg')} role="dialog">
        <div className="flex items-start justify-between gap-4">
          <div><h2 id="reorder-policy-form-title" className="text-lg font-bold text-ink">Create reorder policy</h2><p className="mt-1 text-sm text-muted">The reorder point itself is calculated afterward, never entered directly.</p></div>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>
        <form className="mt-6 grid gap-4" onSubmit={submit}>
          <label className="text-sm font-semibold text-ink">Product
            <select className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={productId} onChange={(event) => setProductId(event.target.value)}>
              <option value="" disabled>Select a product</option>
              {productOptions.map((product) => <option key={product.id} value={product.id}>{product.name} ({product.sku})</option>)}
            </select>
          </label>
          <label className="text-sm font-semibold text-ink">Safety stock quantity
            <input className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" min="0" required step="0.0001" type="number" value={safetyStockQuantity} onChange={(event) => setSafetyStockQuantity(event.target.value)} />
          </label>
          <label className="text-sm font-semibold text-ink">Safety stock basis
            <select className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={safetyStockBasis} onChange={(event) => setSafetyStockBasis(event.target.value as SafetyStockBasis)}>
              <option value="policy_minimum">Policy minimum</option>
              <option value="service_level">Service level</option>
              <option value="manual_override">Manual override</option>
            </select>
          </label>
          <label className="text-sm font-semibold text-ink">Lead time override (days, optional)
            <input className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" min="0" placeholder="Uses the product default lead time if left blank" step="0.01" type="number" value={leadTimeDaysOverride} onChange={(event) => setLeadTimeDaysOverride(event.target.value)} />
          </label>
          <div className="flex justify-end gap-3 border-t border-border pt-5"><Button type="button" variant="secondary" onClick={onClose}>Cancel</Button><Button disabled={isSaving || !isValid} type="submit">{isSaving ? 'Saving' : 'Create policy'}</Button></div>
        </form>
      </section>
    </div>
    </Portal>
  )
}
