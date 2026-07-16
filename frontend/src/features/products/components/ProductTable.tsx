import { Archive, Edit3, PanelRightOpen } from 'lucide-react'
import type { Product } from '@/features/products/types/product'
import { StockBadge } from '@/features/products/components/StockBadge'
import { Button } from '@/shared/components/Button'

type ProductTableProps = {
  products: Product[]
  onEdit: (product: Product) => void
  onView: (product: Product) => void
  onArchive: (product: Product) => void
}

export function ProductTable({ products, onEdit, onView, onArchive }: ProductTableProps) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[950px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr><th className="px-5 py-3">Product</th><th className="px-5 py-3">Category</th><th className="px-5 py-3">SKU</th><th className="px-5 py-3">Stock unit</th><th className="px-5 py-3">Stock status</th><th className="px-5 py-3">State</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {products.map((product) => (
              <tr key={product.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4 font-semibold text-ink">{product.name}</td>
                <td className="px-5 py-4 text-muted">{product.category?.name ?? '—'}</td>
                <td className="px-5 py-4 font-mono text-xs text-muted">{product.sku}</td>
                <td className="px-5 py-4 text-muted">{product.stockUnit?.symbol ?? '—'}</td>
                <td className="px-5 py-4"><StockBadge stock={product.stock} /></td>
                <td className="px-5 py-4"><span className={product.isActive ? 'text-sm font-medium text-emerald-700' : 'text-sm font-medium text-slate-600'}>{product.isActive ? 'Active' : 'Inactive'}</span></td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`View ${product.name}`} size="icon" variant="ghost" onClick={() => onView(product)}><PanelRightOpen aria-hidden="true" size={17} /></Button><Button aria-label={`Edit ${product.name}`} size="icon" variant="ghost" onClick={() => onEdit(product)}><Edit3 aria-hidden="true" size={16} /></Button><Button aria-label={`Archive ${product.name}`} size="icon" variant="ghost" onClick={() => onArchive(product)}><Archive aria-hidden="true" size={16} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
