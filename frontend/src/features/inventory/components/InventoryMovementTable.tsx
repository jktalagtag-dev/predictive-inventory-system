import type { InventoryMovement } from '@/features/inventory/types/inventory'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

const movementTypeLabels: Record<InventoryMovement['movementType'], string> = {
  receipt: 'Receipt',
  sale: 'Sale',
  adjustment: 'Adjustment',
  return: 'Return',
  reservation: 'Reservation',
  release: 'Release',
  reversal: 'Reversal',
}

export function InventoryMovementTable({ movements }: { movements: InventoryMovement[] }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {movements.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No movements match these filters.</p>
        ) : (
          movements.map((movement) => {
            const isPositive = Number(movement.quantityDelta) > 0
            return (
              <RecordCard
                key={movement.id}
                badge={<span className="inline-flex rounded-full bg-subtle px-2.5 py-1 text-xs font-semibold text-muted">{movementTypeLabels[movement.movementType]}</span>}
                title={movement.product?.name ?? '—'}
                subtitle={<span className="font-mono">{movement.product?.sku ?? '—'}</span>}
                fields={[
                  { label: 'Quantity', value: <span className={`font-semibold ${isPositive ? 'text-success-text' : 'text-danger-text'}`}>{isPositive ? '+' : ''}{movement.quantityDelta}</span> },
                  { label: 'Balance after', value: movement.onHandAfterQuantity ?? '—' },
                  { label: 'Reference', value: <span className="font-mono text-xs">{movement.referenceType} #{movement.referenceId}</span>, full: true },
                  { label: 'Actor', value: movement.actor?.displayName ?? '—' },
                  { label: 'Effective', value: movement.effectiveAt ? new Date(movement.effectiveAt).toLocaleString() : '—' },
                ]}
              />
            )
          })
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={800}>
          <TableHead>
            <tr>
              <TableHeaderCell>Product</TableHeaderCell>
              <TableHeaderCell>Type</TableHeaderCell>
              <TableHeaderCell align="right">Quantity</TableHeaderCell>
              <TableHeaderCell align="right">Balance after</TableHeaderCell>
              <TableHeaderCell>Reference</TableHeaderCell>
              <TableHeaderCell>Actor</TableHeaderCell>
              <TableHeaderCell>Effective</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {movements.length === 0 ? (
              <TableEmptyState colSpan={7}>No movements match these filters.</TableEmptyState>
            ) : (
              movements.map((movement) => {
                const isPositive = Number(movement.quantityDelta) > 0
                return (
                  <TableRow key={movement.id}>
                    <TableCell>
                      <p className="font-medium text-ink">{movement.product?.name ?? '—'}</p>
                      <p className="text-xs text-muted">{movement.product?.sku ?? '—'}</p>
                    </TableCell>
                    <TableCell className="text-muted">{movementTypeLabels[movement.movementType]}</TableCell>
                    <TableCell align="right" className={`font-semibold ${isPositive ? 'text-success-text' : 'text-danger-text'}`}>
                      {isPositive ? '+' : ''}{movement.quantityDelta}
                    </TableCell>
                    <TableCell align="right" className="text-muted">{movement.onHandAfterQuantity ?? '—'}</TableCell>
                    <TableCell className="font-mono text-xs text-muted">{movement.referenceType} #{movement.referenceId}</TableCell>
                    <TableCell className="text-muted">{movement.actor?.displayName ?? '—'}</TableCell>
                    <TableCell className="text-muted">{movement.effectiveAt ? new Date(movement.effectiveAt).toLocaleString() : '—'}</TableCell>
                  </TableRow>
                )
              })
            )}
          </TableBody>
        </Table>
      </div>
    </>
  )
}
