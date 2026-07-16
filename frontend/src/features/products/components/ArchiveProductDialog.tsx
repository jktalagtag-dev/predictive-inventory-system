import { X } from 'lucide-react'
import type { Product } from '@/features/products/types/product'
import { Button } from '@/shared/components/Button'

export function ArchiveProductDialog({ product, isArchiving, onClose, onConfirm }: { product: Product; isArchiving: boolean; onClose: () => void; onConfirm: () => void }) {
  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation">
      <section aria-labelledby="archive-product-title" aria-modal="true" className="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-panel" role="dialog">
        <div className="flex items-start justify-between gap-4">
          <h2 id="archive-product-title" className="text-lg font-bold text-ink">Archive product</h2>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>
        <p className="mt-3 text-sm text-muted">
          <strong className="text-ink">{product.name}</strong> ({product.sku}) will be retired and removed from selection for new transactions.
          It remains resolvable in any historical records that already reference it.
          {' '}This action is reversible by an owner or manager through product restoration.
        </p>
        <div className="mt-6 flex justify-end gap-3 border-t border-border pt-5">
          <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
          <Button disabled={isArchiving} variant="danger" onClick={onConfirm}>{isArchiving ? 'Archiving' : 'Archive product'}</Button>
        </div>
      </section>
    </div>
  )
}
