import { Archive, Edit3, PanelRightOpen } from 'lucide-react'
import type { Product } from '@/features/products/types/product'
import { StockBadge } from '@/features/products/components/StockBadge'
import { Avatar } from '@/shared/components/Avatar'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

type ProductTableProps = {
  products: Product[]
  onEdit: (product: Product) => void
  onView: (product: Product) => void
  onArchive: (product: Product) => void
}

export function ProductTable({ products, onEdit, onView, onArchive }: ProductTableProps) {
  return (
    <>
      {/* Mobile: card list (no horizontal scroll) */}
      <div className="space-y-3 md:hidden">
        {products.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No products match these filters.</p>
        ) : (
          products.map((product) => (
            <RecordCard
              key={product.id}
              ariaLabel={`View ${product.name}`}
              badge={<StockBadge stock={product.stock} />}
              title={
                <span className="flex items-center gap-3">
                  <Avatar className="rounded-md" name={product.name} src={product.imageUrl} size="sm" />
                  <span className="truncate">{product.name}</span>
                </span>
              }
              subtitle={<span className="font-mono">{product.sku}</span>}
              fields={[
                { label: 'Category', value: product.category?.name ?? '—' },
                { label: 'Unit', value: product.stockUnit?.symbol ?? '—' },
                { label: 'Price', value: product.sellingPrice },
                { label: 'State', value: product.isActive ? 'Active' : 'Inactive' },
              ]}
              actions={
                <>
                  <Button aria-label={`Edit ${product.name}`} size="icon" variant="ghost" onClick={() => onEdit(product)}><Edit3 aria-hidden="true" size={16} /></Button>
                  <Button aria-label={`Archive ${product.name}`} size="icon" variant="ghost" onClick={() => onArchive(product)}><Archive aria-hidden="true" size={16} /></Button>
                </>
              }
              onClick={() => onView(product)}
            />
          ))
        )}
      </div>

      {/* Desktop: full table */}
      <div className="hidden md:block">
        <Table minWidth={950}>
          <TableHead>
            <tr>
              <TableHeaderCell>Product</TableHeaderCell>
              <TableHeaderCell>Category</TableHeaderCell>
              <TableHeaderCell>SKU</TableHeaderCell>
              <TableHeaderCell>Stock unit</TableHeaderCell>
              <TableHeaderCell align="right">Price</TableHeaderCell>
              <TableHeaderCell>Stock status</TableHeaderCell>
              <TableHeaderCell>State</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {products.length === 0 ? (
              <TableEmptyState colSpan={8}>No products match these filters.</TableEmptyState>
            ) : (
              products.map((product) => (
                <TableRow key={product.id}>
                  <TableCell>
                    <div className="flex items-center gap-3">
                      <Avatar className="rounded-md" name={product.name} src={product.imageUrl} size="sm" />
                      <span className="font-semibold text-ink">{product.name}</span>
                    </div>
                  </TableCell>
                  <TableCell className="text-muted">{product.category?.name ?? '—'}</TableCell>
                  <TableCell className="font-mono text-xs text-muted">{product.sku}</TableCell>
                  <TableCell className="text-muted">{product.stockUnit?.symbol ?? '—'}</TableCell>
                  <TableCell align="right">{product.sellingPrice}</TableCell>
                  <TableCell><StockBadge stock={product.stock} /></TableCell>
                  <TableCell>
                    <span className={product.isActive ? 'text-sm font-medium text-success-text' : 'text-sm font-medium text-muted'}>
                      {product.isActive ? 'Active' : 'Inactive'}
                    </span>
                  </TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`View ${product.name}`} size="icon" variant="ghost" onClick={() => onView(product)}><PanelRightOpen aria-hidden="true" size={18} /></Button>
                      <Button aria-label={`Edit ${product.name}`} size="icon" variant="ghost" onClick={() => onEdit(product)}><Edit3 aria-hidden="true" size={16} /></Button>
                      <Button aria-label={`Archive ${product.name}`} size="icon" variant="ghost" onClick={() => onArchive(product)}><Archive aria-hidden="true" size={16} /></Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </>
  )
}
