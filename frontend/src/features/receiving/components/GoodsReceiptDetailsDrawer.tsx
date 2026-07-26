import { useState } from 'react'
import { X } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthProvider'
import type { GoodsReceipt } from '@/features/receiving/types/goodsReceipt'
import { GoodsReceiptStatusBadge } from '@/features/receiving/components/GoodsReceiptStatusBadge'
import { ReasonPromptDialog } from '@/features/receiving/components/ReasonPromptDialog'
import { Button } from '@/shared/components/Button'
import { drawerOverlayClass, drawerPanelClass } from '@/shared/lib/modalClasses'
import { Portal } from '@/shared/components/Portal'

type GoodsReceiptDetailsDrawerProps = {
  goodsReceipt: GoodsReceipt
  isActing: boolean
  onClose: () => void
  onPost: () => void
  onReverse: (reason: string) => void
}

export function GoodsReceiptDetailsDrawer({ goodsReceipt: receipt, isActing, onClose, onPost, onReverse }: GoodsReceiptDetailsDrawerProps) {
  const { hasPermission } = useAuth()
  const [showReversePrompt, setShowReversePrompt] = useState(false)

  return (
    <Portal>
    <div className={drawerOverlayClass} role="presentation" onMouseDown={onClose}>
      <aside aria-labelledby="gr-details-title" aria-modal="true" className={drawerPanelClass()} role="dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="flex items-start justify-between gap-4 border-b border-border p-4 sm:p-6">
          <div>
            <p className="font-mono text-xs text-muted">{receipt.receiptNumber}</p>
            <h2 id="gr-details-title" className="mt-1 text-xl font-bold tracking-tight text-ink">{receipt.supplierDeliveryNumber ?? 'No delivery reference'}</h2>
            <div className="mt-3"><GoodsReceiptStatusBadge status={receipt.status} /></div>
          </div>
          <Button aria-label="Close goods receipt details" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </header>
        <div className="flex-1 space-y-6 overflow-y-auto p-4 sm:p-6">
          <section>
            <h3 className="text-sm font-semibold text-ink">Lines</h3>
            <div className="mt-3 space-y-2 sm:hidden">
              {receipt.lines.map((line) => (
                <div className="rounded-xl border border-border p-3 text-sm" key={line.id}>
                  <p className="font-medium text-ink">{line.productName}</p>
                  <p className="text-xs text-muted">{line.productSku}</p>
                  <dl className="mt-2 grid grid-cols-3 gap-2 text-xs">
                    <div><dt className="text-muted">Received</dt><dd className="tabular-nums text-ink">{line.receivedQuantity}</dd></div>
                    <div><dt className="text-muted">Accepted</dt><dd className="tabular-nums text-ink">{line.acceptedQuantity}</dd></div>
                    <div><dt className="text-muted">Rejected</dt><dd className="tabular-nums text-ink">{line.rejectedQuantity}</dd></div>
                  </dl>
                </div>
              ))}
            </div>
            <div className="mt-3 hidden overflow-x-auto rounded-xl border border-border sm:block">
              <table className="w-full min-w-[550px] text-sm">
                <thead className="bg-subtle text-left text-xs font-semibold text-muted"><tr><th className="px-3 py-2">Product</th><th className="px-3 py-2 text-right">Received</th><th className="px-3 py-2 text-right">Accepted</th><th className="px-3 py-2 text-right">Rejected</th></tr></thead>
                <tbody className="divide-y divide-border">
                  {receipt.lines.map((line) => (
                    <tr key={line.id}>
                      <td className="px-3 py-2"><p className="font-medium text-ink">{line.productName}</p><p className="text-xs text-muted">{line.productSku}</p></td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.receivedQuantity}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.acceptedQuantity}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.rejectedQuantity}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
          {receipt.lines.some((line) => line.rejectionReason) ? (
            <section>
              <h3 className="text-sm font-semibold text-ink">Rejections</h3>
              <ul className="mt-3 space-y-2">
                {receipt.lines.filter((line) => line.rejectionReason).map((line) => (
                  <li className="rounded-xl border border-border p-3 text-sm" key={line.id}>
                    <p className="font-medium text-ink">{line.productName}</p>
                    <p className="mt-1 text-muted">{line.rejectionReason}</p>
                  </li>
                ))}
              </ul>
            </section>
          ) : null}
          {receipt.notes ? <section><h3 className="text-sm font-semibold text-ink">Notes</h3><p className="mt-2 whitespace-pre-wrap text-sm text-muted">{receipt.notes}</p></section> : null}
        </div>
        <footer className="flex flex-wrap gap-2 border-t border-border p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:p-6">
          {receipt.status === 'draft' && hasPermission('goods_receipts.post') ? <Button disabled={isActing} onClick={onPost}>Post receipt</Button> : null}
          {receipt.status === 'posted' && hasPermission('goods_receipts.reverse') ? <Button disabled={isActing} variant="danger" onClick={() => setShowReversePrompt(true)}>Reverse</Button> : null}
        </footer>
      </aside>
      {showReversePrompt ? (
        <ReasonPromptDialog
          confirmLabel="Reverse receipt"
          description="This creates a compensating receipt that reverses the posted stock movement and cannot be undone."
          isSubmitting={isActing}
          title="Reverse goods receipt"
          onClose={() => setShowReversePrompt(false)}
          onConfirm={(reason) => { onReverse(reason); setShowReversePrompt(false) }}
        />
      ) : null}
    </div>
    </Portal>
  )
}
