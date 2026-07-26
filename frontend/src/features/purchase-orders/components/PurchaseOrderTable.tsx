import { PanelRightOpen } from 'lucide-react'
import type { PurchaseOrder } from '@/features/purchase-orders/types/purchaseOrder'
import { PurchaseOrderStatusBadge } from '@/features/purchase-orders/components/PurchaseOrderStatusBadge'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

export function PurchaseOrderTable({ purchaseOrders, onView }: { purchaseOrders: PurchaseOrder[]; onView: (po: PurchaseOrder) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {purchaseOrders.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No purchase orders match these filters.</p>
        ) : (
          purchaseOrders.map((po) => (
            <RecordCard
              key={po.id}
              ariaLabel={`View ${po.poNumber}`}
              badge={<PurchaseOrderStatusBadge status={po.status} />}
              title={<span className="font-mono">{po.poNumber}</span>}
              subtitle={po.supplier?.legalName ?? undefined}
              fields={[
                { label: 'Total', value: `${po.currencyCode} ${po.totalAmount}` },
                { label: 'Expected', value: po.expectedReceiptAt ? new Date(po.expectedReceiptAt).toLocaleDateString() : '—' },
              ]}
              onClick={() => onView(po)}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={850}>
          <TableHead>
            <tr>
              <TableHeaderCell>PO number</TableHeaderCell>
              <TableHeaderCell>Supplier</TableHeaderCell>
              <TableHeaderCell>Status</TableHeaderCell>
              <TableHeaderCell align="right">Total</TableHeaderCell>
              <TableHeaderCell>Expected receipt</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {purchaseOrders.length === 0 ? (
              <TableEmptyState colSpan={6}>No purchase orders match these filters.</TableEmptyState>
            ) : (
              purchaseOrders.map((po) => (
                <TableRow key={po.id}>
                  <TableCell className="font-mono text-xs font-semibold text-ink">{po.poNumber}</TableCell>
                  <TableCell className="text-muted">{po.supplier?.legalName ?? '—'}</TableCell>
                  <TableCell><PurchaseOrderStatusBadge status={po.status} /></TableCell>
                  <TableCell align="right" className="text-ink">{po.currencyCode} {po.totalAmount}</TableCell>
                  <TableCell className="text-muted">{po.expectedReceiptAt ? new Date(po.expectedReceiptAt).toLocaleDateString() : '—'}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`View ${po.poNumber}`} size="icon" variant="ghost" onClick={() => onView(po)}><PanelRightOpen aria-hidden="true" size={18} /></Button>
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
