import { X } from 'lucide-react'
import type { Product } from '@/features/products/types/product'
import { StockBadge } from '@/features/products/components/StockBadge'
import { Button } from '@/shared/components/Button'

export function ProductDetailsDrawer({ product, onClose, onEdit }: { product: Product; onClose: () => void; onEdit: (product: Product) => void }) {
  return (
    <div className="fixed inset-0 z-40 bg-slate-950/30" role="presentation" onMouseDown={onClose}>
      <aside aria-labelledby="product-details-title" aria-modal="true" className="ml-auto flex h-full w-full max-w-xl flex-col border-l border-border bg-surface shadow-panel" role="dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="flex items-start justify-between gap-4 border-b border-border p-6"><div><p className="font-mono text-xs text-muted">{product.sku}</p><h2 id="product-details-title" className="mt-1 text-xl font-bold tracking-tight text-ink">{product.name}</h2><div className="mt-3"><StockBadge status={product.stock.status} /></div></div><Button aria-label="Close product details" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button></header>
        <div className="flex-1 space-y-6 overflow-y-auto p-6"><section><h3 className="text-sm font-semibold text-ink">Inventory position</h3><dl className="mt-3 grid grid-cols-2 gap-3"><Detail label="On hand" value={product.stock.onHand} /><Detail label="Available" value={product.stock.available} /><Detail label="Incoming" value={product.stock.incoming} /><Detail label="Reorder point" value={product.stock.reorderPoint} /></dl></section><section><h3 className="text-sm font-semibold text-ink">Product details</h3><dl className="mt-3 space-y-3"><Detail label="Category" value={product.category} /><Detail label="Stock unit" value={product.stockUnit} /><Detail label="Product type" value={product.productType.replace('_', ' ')} /><Detail label="Tax rate" value={`${product.taxRate}%`} /><Detail label="Barcode" value={product.barcode ?? 'Not assigned'} /><Detail label="Status" value={product.isActive ? 'Active' : 'Inactive'} /></dl></section></div>
        <footer className="border-t border-border p-6"><Button className="w-full" onClick={() => onEdit(product)}>Edit product</Button></footer>
      </aside>
    </div>
  )
}

function Detail({ label, value }: { label: string; value: string }) {
  return <div><dt className="text-xs font-semibold uppercase tracking-wide text-muted">{label}</dt><dd className="mt-1 capitalize text-sm text-ink">{value}</dd></div>
}
