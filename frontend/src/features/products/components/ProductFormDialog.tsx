import { type FormEvent, useEffect, useState } from 'react'
import { X } from 'lucide-react'
import type { Product, ProductFormValues } from '@/features/products/types/product'
import { Button } from '@/shared/components/Button'

type ProductFormDialogProps = { product?: Product; isSaving: boolean; onClose: () => void; onSave: (values: ProductFormValues) => void }

function valuesFrom(product?: Product): ProductFormValues {
  return product ? { sku: product.sku, barcode: product.barcode ?? '', name: product.name, category: product.category, stockUnit: product.stockUnit, productType: product.productType, taxRate: product.taxRate, isActive: product.isActive } : { sku: '', barcode: '', name: '', category: '', stockUnit: 'EA', productType: 'stock', taxRate: '12.0000', isActive: true }
}

export function ProductFormDialog({ product, isSaving, onClose, onSave }: ProductFormDialogProps) {
  const [values, setValues] = useState<ProductFormValues>(() => valuesFrom(product))
  useEffect(() => setValues(valuesFrom(product)), [product])
  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); onSave(values) }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation">
      <section aria-labelledby="product-form-title" aria-modal="true" className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-border bg-surface p-6 shadow-panel" role="dialog">
        <div className="flex items-start justify-between gap-4"><div><h2 id="product-form-title" className="text-lg font-bold text-ink">{product ? 'Edit product' : 'Create product'}</h2><p className="mt-1 text-sm text-muted">Product setup is validated by the inventory API before it becomes operational.</p></div><Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button></div>
        <form className="mt-6 grid gap-4 sm:grid-cols-2" onSubmit={submit}>
          <label className="text-sm font-semibold text-ink">Product name<input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.name} onChange={(event) => setValues((state) => ({ ...state, name: event.target.value }))} /></label>
          <label className="text-sm font-semibold text-ink">SKU<input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.sku} onChange={(event) => setValues((state) => ({ ...state, sku: event.target.value }))} /></label>
          <label className="text-sm font-semibold text-ink">Category<input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.category} onChange={(event) => setValues((state) => ({ ...state, category: event.target.value }))} /></label>
          <label className="text-sm font-semibold text-ink">Stock unit<input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm uppercase outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.stockUnit} onChange={(event) => setValues((state) => ({ ...state, stockUnit: event.target.value.toUpperCase() }))} /></label>
          <label className="text-sm font-semibold text-ink">Barcode<input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.barcode} onChange={(event) => setValues((state) => ({ ...state, barcode: event.target.value }))} /></label>
          <label className="text-sm font-semibold text-ink">Tax rate<input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" min="0" required step="0.0001" type="number" value={values.taxRate} onChange={(event) => setValues((state) => ({ ...state, taxRate: event.target.value }))} /></label>
          <label className="text-sm font-semibold text-ink">Product type<select className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.productType} onChange={(event) => setValues((state) => ({ ...state, productType: event.target.value as ProductFormValues['productType'] }))}><option value="stock">Stock product</option><option value="non_stock">Non-stock product</option><option value="service">Service</option></select></label>
          <label className="flex items-center gap-2 self-end pb-2 text-sm text-ink"><input checked={values.isActive} type="checkbox" onChange={(event) => setValues((state) => ({ ...state, isActive: event.target.checked }))} /> Active for new transactions</label>
          <div className="col-span-full flex justify-end gap-3 border-t border-border pt-5"><Button type="button" variant="secondary" onClick={onClose}>Cancel</Button><Button disabled={isSaving} type="submit">{isSaving ? 'Saving' : product ? 'Save changes' : 'Create product'}</Button></div>
        </form>
      </section>
    </div>
  )
}
