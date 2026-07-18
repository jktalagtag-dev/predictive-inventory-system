import { type FormEvent, useEffect, useState } from 'react'
import { X } from 'lucide-react'
import type { Category, CategoryFormValues } from '@/features/categories/types/category'
import { Button } from '@/shared/components/Button'

type CategoryFormDialogProps = { category?: Category; parentOptions: Category[]; isSaving: boolean; onClose: () => void; onSave: (values: CategoryFormValues) => void }

function valuesFrom(category?: Category): CategoryFormValues {
  return category ? { parentCategoryId: category.parentCategoryId ?? '', code: category.code, name: category.name, description: category.description ?? '', isActive: category.isActive } : { parentCategoryId: '', code: '', name: '', description: '', isActive: true }
}

export function CategoryFormDialog({ category, parentOptions, isSaving, onClose, onSave }: CategoryFormDialogProps) {
  const [values, setValues] = useState<CategoryFormValues>(() => valuesFrom(category))
  useEffect(() => setValues(valuesFrom(category)), [category])
  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); onSave(values) }
  const availableParents = parentOptions.filter((option) => option.id !== category?.id)

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation">
      <section aria-labelledby="category-form-title" aria-modal="true" className="w-full max-w-lg rounded-card border border-border bg-surface p-8 shadow-panel" role="dialog">
        <div className="flex items-start justify-between gap-4"><div><h2 id="category-form-title" className="text-lg font-bold text-ink">{category ? 'Edit category' : 'Create category'}</h2><p className="mt-1 text-sm text-muted">Categories classify products and control new-product eligibility.</p></div><Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button></div>
        <form className="mt-6 space-y-4" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <label className="text-sm font-semibold text-ink">Category name<input className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.name} onChange={(event) => setValues((state) => ({ ...state, name: event.target.value }))} /></label>
            <label className="text-sm font-semibold text-ink">Code<input className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm uppercase outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.code} onChange={(event) => setValues((state) => ({ ...state, code: event.target.value.toUpperCase() }))} /></label>
          </div>
          <label className="block text-sm font-semibold text-ink">Parent category
            <select className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.parentCategoryId} onChange={(event) => setValues((state) => ({ ...state, parentCategoryId: event.target.value }))}>
              <option value="">No parent (top level)</option>
              {availableParents.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}
            </select>
          </label>
          <label className="block text-sm font-semibold text-ink">Description
            <textarea className="mt-2 w-full rounded-xl border border-border bg-surface px-3 py-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" maxLength={1000} rows={3} value={values.description} onChange={(event) => setValues((state) => ({ ...state, description: event.target.value }))} />
          </label>
          <label className="flex items-center gap-2 text-sm text-ink"><input checked={values.isActive} type="checkbox" onChange={(event) => setValues((state) => ({ ...state, isActive: event.target.checked }))} /> Eligible for new products</label>
          <div className="flex justify-end gap-3 border-t border-border pt-5">
            <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
            <Button disabled={isSaving} type="submit">{isSaving ? 'Saving' : category ? 'Save changes' : 'Create category'}</Button>
          </div>
        </form>
      </section>
    </div>
  )
}
