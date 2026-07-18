import { Edit3 } from 'lucide-react'
import type { Supplier } from '@/features/suppliers/types/supplier'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

const statusBadge = (isActive: boolean) => (
  <span className={isActive ? 'inline-flex rounded-full bg-success/10 px-2.5 py-1 text-xs font-semibold text-success-text' : 'inline-flex rounded-full bg-subtle px-2.5 py-1 text-xs font-semibold text-muted'}>
    {isActive ? 'Active' : 'Inactive'}
  </span>
)

export function SupplierTable({ suppliers, onEdit }: { suppliers: Supplier[]; onEdit: (supplier: Supplier) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {suppliers.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No suppliers match these filters.</p>
        ) : (
          suppliers.map((supplier) => (
            <RecordCard
              key={supplier.id}
              badge={statusBadge(supplier.isActive)}
              title={supplier.legalName}
              subtitle={<span className="font-mono">{supplier.code}</span>}
              fields={[
                { label: 'Contact', value: supplier.email ?? supplier.phone ?? '—', full: true },
                { label: 'Currency', value: supplier.defaultCurrencyCode },
              ]}
              actions={<Button aria-label={`Edit ${supplier.legalName}`} size="icon" variant="ghost" onClick={() => onEdit(supplier)}><Edit3 aria-hidden="true" size={16} /></Button>}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={850}>
          <TableHead>
            <tr>
              <TableHeaderCell>Supplier</TableHeaderCell>
              <TableHeaderCell>Code</TableHeaderCell>
              <TableHeaderCell>Contact</TableHeaderCell>
              <TableHeaderCell>Currency</TableHeaderCell>
              <TableHeaderCell>Status</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {suppliers.length === 0 ? (
              <TableEmptyState colSpan={6}>No suppliers match these filters.</TableEmptyState>
            ) : (
              suppliers.map((supplier) => (
                <TableRow key={supplier.id}>
                  <TableCell className="font-semibold text-ink">{supplier.legalName}</TableCell>
                  <TableCell className="font-mono text-xs text-muted">{supplier.code}</TableCell>
                  <TableCell className="text-muted">{supplier.email ?? supplier.phone ?? '—'}</TableCell>
                  <TableCell className="text-muted">{supplier.defaultCurrencyCode}</TableCell>
                  <TableCell>{statusBadge(supplier.isActive)}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`Edit ${supplier.legalName}`} size="icon" variant="ghost" onClick={() => onEdit(supplier)}><Edit3 aria-hidden="true" size={16} /></Button>
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
