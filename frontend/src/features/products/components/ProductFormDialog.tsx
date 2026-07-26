import { type FormEvent, useEffect, useState } from 'react'
import { X } from 'lucide-react'
import type { CategoryOption, Product, ProductFormValues, UnitOption } from '@/features/products/types/product'
import { Button } from '@/shared/components/Button'
import { cn } from '@/shared/lib/cn'
import { modalOverlayClass, modalPanelClass, sheetBodyClass, sheetFooterClass, sheetHeaderClass } from '@/shared/lib/modalClasses'
import { Portal } from '@/shared/components/Portal'

type ProductFormDialogProps = {
  product?: Product
  categoryOptions: CategoryOption[]
  unitOptions: UnitOption[]
  isSaving: boolean
  onClose: () => void
  onSave: (values: ProductFormValues) => void
}

function valuesFrom(product?: Product): ProductFormValues {
  return product
    ? {
        categoryId: product.category?.id ?? '',
        stockUnitId: product.stockUnit?.id ?? '',
        sku: product.sku,
        barcode: product.barcode ?? '',
        name: product.name,
        description: product.description ?? '',
        productType: product.productType,
        defaultTaxRate: product.defaultTaxRate,
        sellingPrice: product.sellingPrice,
        isActive: product.isActive,
        isLotTracked: product.isLotTracked,
        isSerialTracked: product.isSerialTracked,
        isExpiryTracked: product.isExpiryTracked,
      }
    : {
        categoryId: '',
        stockUnitId: '',
        sku: '',
        barcode: '',
        name: '',
        description: '',
        productType: 'stock',
        defaultTaxRate: '12.0000',
        sellingPrice: '0.0000',
        isActive: true,
        isLotTracked: false,
        isSerialTracked: false,
        isExpiryTracked: false,
      }
}

export function ProductFormDialog({ product, categoryOptions, unitOptions, isSaving, onClose, onSave }: ProductFormDialogProps) {
  const [values, setValues] = useState<ProductFormValues>(() => valuesFrom(product))
  useEffect(() => setValues(valuesFrom(product)), [product])
  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); onSave(values) }
  const isValid = values.categoryId !== '' && values.stockUnitId !== ''

  return (
    <Portal>
    <div className={modalOverlayClass} role="presentation">
      <section aria-labelledby="product-form-title" aria-modal="true" className={modalPanelClass('sm:max-w-2xl')} role="dialog">
        <div className={sheetHeaderClass}><div><h2 id="product-form-title" className="text-lg font-bold text-ink">{product ? 'Edit product' : 'Create product'}</h2><p className="mt-1 text-sm text-muted">Product setup is validated by the inventory API before it becomes operational.</p></div><Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button></div>
        <form className="flex min-h-0 flex-1 flex-col" onSubmit={submit}>
          <div className={cn(sheetBodyClass, 'grid gap-4 sm:grid-cols-2')}>
            <label className="text-sm font-semibold text-ink">Product name<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.name} onChange={(event) => setValues((state) => ({ ...state, name: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">SKU<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.sku} onChange={(event) => setValues((state) => ({ ...state, sku: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Category
              <select className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.categoryId} onChange={(event) => setValues((state) => ({ ...state, categoryId: event.target.value }))}>
                <option value="" disabled>Select a category</option>
                {categoryOptions.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
              </select>
            </label>
            <label className="text-sm font-semibold text-ink">Stock unit
              <select className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.stockUnitId} onChange={(event) => setValues((state) => ({ ...state, stockUnitId: event.target.value }))}>
                <option value="" disabled>Select a unit</option>
                {unitOptions.map((unit) => <option key={unit.id} value={unit.id}>{unit.name} ({unit.symbol})</option>)}
              </select>
            </label>
            <label className="text-sm font-semibold text-ink">Barcode<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.barcode} onChange={(event) => setValues((state) => ({ ...state, barcode: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Tax rate<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" min="0" required step="0.0001" type="number" value={values.defaultTaxRate} onChange={(event) => setValues((state) => ({ ...state, defaultTaxRate: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Selling price<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" min="0" required step="0.0001" type="number" value={values.sellingPrice} onChange={(event) => setValues((state) => ({ ...state, sellingPrice: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Product type<select className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.productType} onChange={(event) => setValues((state) => ({ ...state, productType: event.target.value as ProductFormValues['productType'] }))}><option value="stock">Stock product</option><option value="non_stock">Non-stock product</option><option value="service">Service</option></select></label>
            <label className="block text-sm font-semibold text-ink sm:col-span-2">Description
              <textarea className="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" rows={2} value={values.description} onChange={(event) => setValues((state) => ({ ...state, description: event.target.value }))} />
            </label>
            <div className="flex flex-wrap gap-4 sm:col-span-2">
              <label className="flex items-center gap-2 text-sm text-ink"><input checked={values.isActive} type="checkbox" onChange={(event) => setValues((state) => ({ ...state, isActive: event.target.checked }))} /> Active for new transactions</label>
              <label className="flex items-center gap-2 text-sm text-ink"><input checked={values.isLotTracked} type="checkbox" onChange={(event) => setValues((state) => ({ ...state, isLotTracked: event.target.checked }))} /> Lot tracked</label>
              <label className="flex items-center gap-2 text-sm text-ink"><input checked={values.isSerialTracked} type="checkbox" onChange={(event) => setValues((state) => ({ ...state, isSerialTracked: event.target.checked }))} /> Serial tracked</label>
              <label className="flex items-center gap-2 text-sm text-ink"><input checked={values.isExpiryTracked} type="checkbox" onChange={(event) => setValues((state) => ({ ...state, isExpiryTracked: event.target.checked }))} /> Expiry tracked</label>
            </div>
          </div>
          <div className={sheetFooterClass}><Button type="button" variant="secondary" onClick={onClose}>Cancel</Button><Button disabled={isSaving || !isValid} type="submit">{isSaving ? 'Saving' : product ? 'Save changes' : 'Create product'}</Button></div>
        </form>
      </section>
    </div>
    </Portal>
  )
}
