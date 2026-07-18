import { type FormEvent, useEffect, useState } from 'react'
import { X } from 'lucide-react'
import type { Supplier, SupplierFormValues } from '@/features/suppliers/types/supplier'
import { Button } from '@/shared/components/Button'
import { cn } from '@/shared/lib/cn'
import { modalOverlayClass, modalPanelClass, sheetBodyClass, sheetFooterClass, sheetHeaderClass } from '@/shared/lib/modalClasses'

type SupplierFormDialogProps = { supplier?: Supplier; isSaving: boolean; onClose: () => void; onSave: (values: SupplierFormValues) => void }

function valuesFrom(supplier?: Supplier): SupplierFormValues {
  return supplier
    ? {
        code: supplier.code,
        legalName: supplier.legalName,
        taxIdentifier: supplier.taxIdentifier ?? '',
        email: supplier.email ?? '',
        phone: supplier.phone ?? '',
        addressLine1: supplier.addressLine1 ?? '',
        city: supplier.city ?? '',
        province: supplier.province ?? '',
        postalCode: supplier.postalCode ?? '',
        countryCode: supplier.countryCode,
        defaultCurrencyCode: supplier.defaultCurrencyCode,
        isActive: supplier.isActive,
      }
    : {
        code: '', legalName: '', taxIdentifier: '', email: '', phone: '', addressLine1: '',
        city: '', province: '', postalCode: '', countryCode: 'PH', defaultCurrencyCode: 'PHP', isActive: true,
      }
}

export function SupplierFormDialog({ supplier, isSaving, onClose, onSave }: SupplierFormDialogProps) {
  const [values, setValues] = useState<SupplierFormValues>(() => valuesFrom(supplier))
  useEffect(() => setValues(valuesFrom(supplier)), [supplier])
  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); onSave(values) }

  return (
    <div className={modalOverlayClass} role="presentation">
      <section aria-labelledby="supplier-form-title" aria-modal="true" className={modalPanelClass('sm:max-w-2xl')} role="dialog">
        <div className={sheetHeaderClass}><div><h2 id="supplier-form-title" className="text-lg font-bold text-ink">{supplier ? 'Edit supplier' : 'Create supplier'}</h2><p className="mt-1 text-sm text-muted">Suppliers must be deactivated rather than deleted once referenced by a purchase order.</p></div><Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button></div>
        <form className="flex min-h-0 flex-1 flex-col" onSubmit={submit}>
          <div className={cn(sheetBodyClass, 'grid gap-4 sm:grid-cols-2')}>
            <label className="text-sm font-semibold text-ink">Legal name<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.legalName} onChange={(event) => setValues((state) => ({ ...state, legalName: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Code<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm uppercase outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.code} onChange={(event) => setValues((state) => ({ ...state, code: event.target.value.toUpperCase() }))} /></label>
            <label className="text-sm font-semibold text-ink">Tax identifier<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.taxIdentifier} onChange={(event) => setValues((state) => ({ ...state, taxIdentifier: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Email<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" type="email" value={values.email} onChange={(event) => setValues((state) => ({ ...state, email: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Phone<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.phone} onChange={(event) => setValues((state) => ({ ...state, phone: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink sm:col-span-2">Address<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.addressLine1} onChange={(event) => setValues((state) => ({ ...state, addressLine1: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">City<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.city} onChange={(event) => setValues((state) => ({ ...state, city: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Province<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.province} onChange={(event) => setValues((state) => ({ ...state, province: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Postal code<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.postalCode} onChange={(event) => setValues((state) => ({ ...state, postalCode: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Country code<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm uppercase outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" maxLength={2} required value={values.countryCode} onChange={(event) => setValues((state) => ({ ...state, countryCode: event.target.value.toUpperCase() }))} /></label>
            <label className="text-sm font-semibold text-ink">Default currency<input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm uppercase outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" maxLength={3} required value={values.defaultCurrencyCode} onChange={(event) => setValues((state) => ({ ...state, defaultCurrencyCode: event.target.value.toUpperCase() }))} /></label>
            <label className="flex items-center gap-2 self-end pb-2 text-sm text-ink"><input checked={values.isActive} type="checkbox" onChange={(event) => setValues((state) => ({ ...state, isActive: event.target.checked }))} /> Active for new purchase orders</label>
          </div>
          <div className={sheetFooterClass}><Button type="button" variant="secondary" onClick={onClose}>Cancel</Button><Button disabled={isSaving} type="submit">{isSaving ? 'Saving' : supplier ? 'Save changes' : 'Create supplier'}</Button></div>
        </form>
      </section>
    </div>
  )
}
