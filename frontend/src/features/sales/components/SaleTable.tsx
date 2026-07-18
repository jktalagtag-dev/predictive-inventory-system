import { PanelRightOpen } from 'lucide-react'
import type { Sale } from '@/features/sales/types/sale'
import { SaleStatusBadge } from '@/features/sales/components/SaleStatusBadge'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

export function SaleTable({ sales, onView }: { sales: Sale[]; onView: (sale: Sale) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {sales.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No sales match these filters.</p>
        ) : (
          sales.map((sale) => (
            <RecordCard
              key={sale.id}
              ariaLabel={`View ${sale.saleNumber}`}
              badge={<SaleStatusBadge status={sale.status} />}
              title={<span className="font-mono">{sale.saleNumber}</span>}
              subtitle={sale.cashierName ?? undefined}
              fields={[
                { label: 'Sold at', value: sale.soldAt ? new Date(sale.soldAt).toLocaleString() : '—', full: true },
                { label: 'Lines', value: sale.lineCount ?? '—' },
                { label: 'Total', value: sale.totalAmount },
              ]}
              onClick={() => onView(sale)}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={750}>
          <TableHead>
            <tr>
              <TableHeaderCell>Sale number</TableHeaderCell>
              <TableHeaderCell>Cashier</TableHeaderCell>
              <TableHeaderCell>Status</TableHeaderCell>
              <TableHeaderCell>Sold at</TableHeaderCell>
              <TableHeaderCell align="right">Lines</TableHeaderCell>
              <TableHeaderCell align="right">Total</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {sales.length === 0 ? (
              <TableEmptyState colSpan={7}>No sales match these filters.</TableEmptyState>
            ) : (
              sales.map((sale) => (
                <TableRow key={sale.id}>
                  <TableCell className="font-mono text-xs font-semibold text-ink">{sale.saleNumber}</TableCell>
                  <TableCell className="text-muted">{sale.cashierName ?? '—'}</TableCell>
                  <TableCell><SaleStatusBadge status={sale.status} /></TableCell>
                  <TableCell className="text-muted">{sale.soldAt ? new Date(sale.soldAt).toLocaleString() : '—'}</TableCell>
                  <TableCell align="right" className="text-ink">{sale.lineCount ?? '—'}</TableCell>
                  <TableCell align="right" className="font-semibold text-ink">{sale.totalAmount}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`View ${sale.saleNumber}`} size="icon" variant="ghost" onClick={() => onView(sale)}><PanelRightOpen aria-hidden="true" size={17} /></Button>
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
