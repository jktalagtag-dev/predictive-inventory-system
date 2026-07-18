import type { InventoryBalance } from '@/features/inventory/types/inventory'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

export function InventoryBalanceTable({ balances }: { balances: InventoryBalance[] }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {balances.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No inventory balances for this branch yet.</p>
        ) : (
          balances.map((balance) => (
            <RecordCard
              key={balance.id}
              title={balance.product?.name ?? '—'}
              subtitle={<span className="font-mono">{balance.product?.sku ?? '—'}</span>}
              fields={[
                { label: 'On hand', value: balance.onHandQuantity },
                { label: 'Reserved', value: balance.reservedQuantity },
                { label: 'Available', value: <span className={Number(balance.availableQuantity) <= 0 ? 'font-semibold text-danger-text' : 'font-semibold text-ink'}>{balance.availableQuantity}</span> },
                { label: 'Incoming', value: balance.incomingQuantity },
                { label: 'Last movement', value: balance.lastMovementAt ? new Date(balance.lastMovementAt).toLocaleString() : '—', full: true },
              ]}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={750}>
          <TableHead>
            <tr>
              <TableHeaderCell>Product</TableHeaderCell>
              <TableHeaderCell align="right">On hand</TableHeaderCell>
              <TableHeaderCell align="right">Reserved</TableHeaderCell>
              <TableHeaderCell align="right">Available</TableHeaderCell>
              <TableHeaderCell align="right">Incoming</TableHeaderCell>
              <TableHeaderCell>Last movement</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {balances.length === 0 ? (
              <TableEmptyState colSpan={6}>No inventory balances for this branch yet.</TableEmptyState>
            ) : balances.map((balance) => (
              <TableRow key={balance.id}>
                <TableCell>
                  <p className="font-medium text-ink">{balance.product?.name ?? '—'}</p>
                  <p className="text-xs text-muted">{balance.product?.sku ?? '—'}</p>
                </TableCell>
                <TableCell align="right"><span className="text-ink">{balance.onHandQuantity}</span></TableCell>
                <TableCell align="right"><span className="text-muted">{balance.reservedQuantity}</span></TableCell>
                <TableCell align="right" className={`font-semibold ${Number(balance.availableQuantity) <= 0 ? 'text-danger-text' : 'text-ink'}`}>{balance.availableQuantity}</TableCell>
                <TableCell align="right"><span className="text-muted">{balance.incomingQuantity}</span></TableCell>
                <TableCell><span className="text-muted">{balance.lastMovementAt ? new Date(balance.lastMovementAt).toLocaleString() : '—'}</span></TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </>
  )
}
