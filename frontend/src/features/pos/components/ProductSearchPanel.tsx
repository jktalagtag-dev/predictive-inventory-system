import { useRef, useState } from 'react'
import { Search } from 'lucide-react'
import { usePosProducts } from '@/features/pos/hooks/usePos'
import type { PosProduct } from '@/features/pos/types/pos'

type ProductSearchPanelProps = {
  branchId: string | null
  onAdd: (product: PosProduct) => void
}

export function ProductSearchPanel({ branchId, onAdd }: ProductSearchPanelProps) {
  const [query, setQuery] = useState('')
  const inputRef = useRef<HTMLInputElement>(null)
  const productsQuery = usePosProducts(branchId, query)
  const results = productsQuery.data ?? []

  const addFirstMatch = () => {
    if (results.length === 1) {
      onAdd(results[0])
      setQuery('')
      inputRef.current?.focus()
    }
  }

  return (
    <section aria-label="Product search" className="flex h-full flex-col rounded-xl border border-border bg-surface shadow-panel">
      <div className="border-b border-border p-4">
        <label className="sr-only" htmlFor="pos-search">Search products or scan barcode</label>
        <div className="relative">
          <Search aria-hidden="true" className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" size={17} />
          <input
            ref={inputRef}
            autoFocus
            className="h-11 w-full rounded-lg border border-border bg-surface pl-10 pr-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
            id="pos-search"
            placeholder="Search by name, SKU, or scan barcode"
            type="text"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            onKeyDown={(event) => { if (event.key === 'Enter') { event.preventDefault(); addFirstMatch() } }}
          />
        </div>
      </div>
      <div className="flex-1 overflow-y-auto">
        {!branchId ? (
          <p className="p-4 text-sm text-muted">Select a branch to look up sale-eligible products.</p>
        ) : productsQuery.isLoading ? (
          <p className="p-4 text-sm text-muted">Searching…</p>
        ) : results.length === 0 ? (
          <p className="p-4 text-sm text-muted">{query ? 'No matching products.' : 'Start typing to find a product.'}</p>
        ) : (
          <ul className="divide-y divide-border">
            {results.map((product) => {
              const outOfStock = product.productType === 'stock' && Number(product.stock?.availableQuantity ?? '0') <= 0
              return (
                <li key={product.id}>
                  <button
                    className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition-colors hover:bg-subtle disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={outOfStock}
                    type="button"
                    onClick={() => onAdd(product)}
                  >
                    <span>
                      <span className="block text-sm font-semibold text-ink">{product.name}</span>
                      <span className="block font-mono text-xs text-muted">{product.sku}{product.barcode ? ` · ${product.barcode}` : ''}</span>
                      {product.productType === 'stock' ? (
                        <span className={`block text-xs ${outOfStock ? 'text-red-700' : 'text-muted'}`}>
                          {outOfStock ? 'Out of stock' : `${product.stock?.availableQuantity ?? '0'} available`}
                        </span>
                      ) : null}
                    </span>
                    <span className="whitespace-nowrap text-sm font-semibold tabular-nums text-ink">{product.sellingPrice}</span>
                  </button>
                </li>
              )
            })}
          </ul>
        )}
      </div>
    </section>
  )
}
