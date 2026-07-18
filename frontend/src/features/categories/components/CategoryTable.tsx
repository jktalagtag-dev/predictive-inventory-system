import { Archive, Edit3 } from 'lucide-react'
import type { Category } from '@/features/categories/types/category'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

const statusBadge = (isActive: boolean) => (
  <span className={isActive ? 'inline-flex rounded-full bg-success/10 px-2.5 py-1 text-xs font-semibold text-success-text' : 'inline-flex rounded-full bg-subtle px-2.5 py-1 text-xs font-semibold text-muted'}>
    {isActive ? 'Active' : 'Inactive'}
  </span>
)

export function CategoryTable({ categories, onEdit, onArchive }: { categories: Category[]; onEdit: (category: Category) => void; onArchive: (category: Category) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {categories.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No categories match these filters.</p>
        ) : (
          categories.map((category) => (
            <RecordCard
              key={category.id}
              badge={statusBadge(category.isActive)}
              title={category.name}
              subtitle={category.description ?? undefined}
              fields={[
                { label: 'Code', value: <span className="font-mono">{category.code}</span> },
                { label: 'Parent', value: category.parentName ?? 'Top level' },
                { label: 'Products', value: category.productCount },
              ]}
              actions={
                <>
                  <Button aria-label={`Edit ${category.name}`} size="icon" variant="ghost" onClick={() => onEdit(category)}><Edit3 aria-hidden="true" size={16} /></Button>
                  <Button aria-label={`Archive ${category.name}`} size="icon" variant="ghost" onClick={() => onArchive(category)}><Archive aria-hidden="true" size={16} /></Button>
                </>
              }
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={800}>
          <TableHead>
            <tr>
              <TableHeaderCell>Category</TableHeaderCell>
              <TableHeaderCell>Code</TableHeaderCell>
              <TableHeaderCell>Parent</TableHeaderCell>
              <TableHeaderCell align="right">Products</TableHeaderCell>
              <TableHeaderCell>Status</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {categories.length === 0 ? (
              <TableEmptyState colSpan={6}>No categories match these filters.</TableEmptyState>
            ) : (
              categories.map((category) => (
                <TableRow key={category.id}>
                  <TableCell>
                    <p className="font-semibold text-ink">{category.name}</p>
                    {category.description ? <p className="mt-1 text-xs text-muted">{category.description}</p> : null}
                  </TableCell>
                  <TableCell className="font-mono text-xs text-muted">{category.code}</TableCell>
                  <TableCell className="text-muted">{category.parentName ?? 'Top level'}</TableCell>
                  <TableCell align="right" className="text-ink">{category.productCount}</TableCell>
                  <TableCell>{statusBadge(category.isActive)}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`Edit ${category.name}`} size="icon" variant="ghost" onClick={() => onEdit(category)}><Edit3 aria-hidden="true" size={16} /></Button>
                      <Button aria-label={`Archive ${category.name}`} size="icon" variant="ghost" onClick={() => onArchive(category)}><Archive aria-hidden="true" size={16} /></Button>
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
