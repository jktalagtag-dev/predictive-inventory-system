import { Archive, Edit3 } from 'lucide-react'
import type { Category } from '@/features/categories/types/category'
import { Button } from '@/shared/components/Button'

export function CategoryTable({ categories, onEdit, onArchive }: { categories: Category[]; onEdit: (category: Category) => void; onArchive: (category: Category) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[800px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr><th className="px-5 py-3">Category</th><th className="px-5 py-3">Code</th><th className="px-5 py-3">Parent</th><th className="px-5 py-3 text-right">Products</th><th className="px-5 py-3">Status</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {categories.map((category) => (
              <tr key={category.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4"><p className="font-semibold text-ink">{category.name}</p>{category.description ? <p className="mt-1 text-xs text-muted">{category.description}</p> : null}</td>
                <td className="px-5 py-4 font-mono text-xs text-muted">{category.code}</td>
                <td className="px-5 py-4 text-muted">{category.parentName ?? 'Top level'}</td>
                <td className="px-5 py-4 text-right tabular-nums text-ink">{category.productCount}</td>
                <td className="px-5 py-4"><span className={category.isActive ? 'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700' : 'inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700'}>{category.isActive ? 'Active' : 'Inactive'}</span></td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`Edit ${category.name}`} size="icon" variant="ghost" onClick={() => onEdit(category)}><Edit3 aria-hidden="true" size={16} /></Button><Button aria-label={`Archive ${category.name}`} size="icon" variant="ghost" onClick={() => onArchive(category)}><Archive aria-hidden="true" size={16} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
