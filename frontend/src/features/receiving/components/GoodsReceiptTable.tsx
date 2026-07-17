import { PanelRightOpen } from 'lucide-react'
import type { GoodsReceipt } from '@/features/receiving/types/goodsReceipt'
import { GoodsReceiptStatusBadge } from '@/features/receiving/components/GoodsReceiptStatusBadge'
import { Button } from '@/shared/components/Button'

export function GoodsReceiptTable({ goodsReceipts, onView }: { goodsReceipts: GoodsReceipt[]; onView: (receipt: GoodsReceipt) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[750px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr><th className="px-5 py-3">Receipt number</th><th className="px-5 py-3">Delivery ref</th><th className="px-5 py-3">Status</th><th className="px-5 py-3">Received</th><th className="px-5 py-3 text-right">Lines</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {goodsReceipts.map((receipt) => (
              <tr key={receipt.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4 font-mono text-xs font-semibold text-ink">{receipt.receiptNumber}</td>
                <td className="px-5 py-4 text-muted">{receipt.supplierDeliveryNumber ?? '—'}</td>
                <td className="px-5 py-4"><GoodsReceiptStatusBadge status={receipt.status} /></td>
                <td className="px-5 py-4 text-muted">{receipt.receivedAt ? new Date(receipt.receivedAt).toLocaleDateString() : '—'}</td>
                <td className="px-5 py-4 text-right tabular-nums text-ink">{receipt.lineCount ?? '—'}</td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`View ${receipt.receiptNumber}`} size="icon" variant="ghost" onClick={() => onView(receipt)}><PanelRightOpen aria-hidden="true" size={17} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
