import { X } from 'lucide-react'
import type { Category } from '@/features/categories/types/category'
import { Button } from '@/shared/components/Button'
import { Portal } from '@/shared/components/Portal'
import { confirmDialogOverlayClass, confirmDialogPanelClass } from '@/shared/lib/modalClasses'

export function ArchiveCategoryDialog({ category, isArchiving, onClose, onConfirm }: { category: Category; isArchiving: boolean; onClose: () => void; onConfirm: () => void }) {
  return (
    <Portal>
    <div className={confirmDialogOverlayClass} role="presentation">
      <section aria-labelledby="archive-category-title" aria-modal="true" className={confirmDialogPanelClass('max-w-md')} role="dialog">
        <div className="flex items-start justify-between gap-4">
          <h2 id="archive-category-title" className="text-lg font-bold text-ink">Archive category</h2>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>
        <p className="mt-3 text-sm text-muted">
          <strong className="text-ink">{category.name}</strong> ({category.code}) will be retired and removed from selection for new products.
          {category.productCount > 0 ? ` It remains linked to ${category.productCount} existing product${category.productCount === 1 ? '' : 's'} and their historical records.` : ' No products currently reference it.'}
          {' '}This action is reversible by an owner or manager through category restoration.
        </p>
        <div className="mt-6 flex justify-end gap-3 border-t border-border pt-5">
          <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
          <Button disabled={isArchiving} variant="danger" onClick={onConfirm}>{isArchiving ? 'Archiving' : 'Archive category'}</Button>
        </div>
      </section>
    </div>
    </Portal>
  )
}
