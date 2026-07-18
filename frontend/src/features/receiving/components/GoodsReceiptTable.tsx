import { PanelRightOpen } from 'lucide-react'
import type { GoodsReceipt } from '@/features/receiving/types/goodsReceipt'
import { GoodsReceiptStatusBadge } from '@/features/receiving/components/GoodsReceiptStatusBadge'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

export function GoodsReceiptTable({ goodsReceipts, onView }: { goodsReceipts: GoodsReceipt[]; onView: (receipt: GoodsReceipt) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {goodsReceipts.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No goods receipts match these filters.</p>
        ) : (
          goodsReceipts.map((receipt) => (
            <RecordCard
              key={receipt.id}
              ariaLabel={`View ${receipt.receiptNumber}`}
              badge={<GoodsReceiptStatusBadge status={receipt.status} />}
              title={<span className="font-mono">{receipt.receiptNumber}</span>}
              subtitle={receipt.supplierDeliveryNumber ?? undefined}
              fields={[
                { label: 'Received', value: receipt.receivedAt ? new Date(receipt.receivedAt).toLocaleDateString() : '—' },
                { label: 'Lines', value: receipt.lineCount ?? '—' },
              ]}
              onClick={() => onView(receipt)}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={750}>
          <TableHead>
            <tr>
              <TableHeaderCell>Receipt number</TableHeaderCell>
              <TableHeaderCell>Delivery ref</TableHeaderCell>
              <TableHeaderCell>Status</TableHeaderCell>
              <TableHeaderCell>Received</TableHeaderCell>
              <TableHeaderCell align="right">Lines</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {goodsReceipts.length === 0 ? (
              <TableEmptyState colSpan={6}>No goods receipts match these filters.</TableEmptyState>
            ) : (
              goodsReceipts.map((receipt) => (
                <TableRow key={receipt.id}>
                  <TableCell className="font-mono text-xs font-semibold text-ink">{receipt.receiptNumber}</TableCell>
                  <TableCell className="text-muted">{receipt.supplierDeliveryNumber ?? '—'}</TableCell>
                  <TableCell><GoodsReceiptStatusBadge status={receipt.status} /></TableCell>
                  <TableCell className="text-muted">{receipt.receivedAt ? new Date(receipt.receivedAt).toLocaleDateString() : '—'}</TableCell>
                  <TableCell align="right" className="text-ink">{receipt.lineCount ?? '—'}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`View ${receipt.receiptNumber}`} size="icon" variant="ghost" onClick={() => onView(receipt)}><PanelRightOpen aria-hidden="true" size={17} /></Button>
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
