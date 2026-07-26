import { useState } from 'react'
import { X } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthProvider'
import type { PurchaseOrder } from '@/features/purchase-orders/types/purchaseOrder'
import { PurchaseOrderStatusBadge } from '@/features/purchase-orders/components/PurchaseOrderStatusBadge'
import { ReasonPromptDialog } from '@/features/purchase-orders/components/ReasonPromptDialog'
import { Button } from '@/shared/components/Button'
import { drawerOverlayClass, drawerPanelClass } from '@/shared/lib/modalClasses'
import { Portal } from '@/shared/components/Portal'

type PurchaseOrderDetailsDrawerProps = {
  purchaseOrder: PurchaseOrder
  isActing: boolean
  onClose: () => void
  onSubmit: () => void
  onApprove: () => void
  onReject: (reason: string) => void
  onMarkOrdered: () => void
  onCancel: (reason: string) => void
  onClosePo: () => void
}

export function PurchaseOrderDetailsDrawer({ purchaseOrder: po, isActing, onClose, onSubmit, onApprove, onReject, onMarkOrdered, onCancel, onClosePo }: PurchaseOrderDetailsDrawerProps) {
  const { hasPermission } = useAuth()
  const [prompt, setPrompt] = useState<'reject' | 'cancel' | null>(null)

  const canCancel = hasPermission('purchase_orders.cancel') && ['draft', 'submitted', 'approved', 'ordered'].includes(po.status)

  return (
    <Portal>
    <div className={drawerOverlayClass} role="presentation" onMouseDown={onClose}>
      <aside aria-labelledby="po-details-title" aria-modal="true" className={drawerPanelClass()} role="dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="flex items-start justify-between gap-4 border-b border-border p-4 sm:p-6">
          <div><p className="font-mono text-xs text-muted">{po.poNumber}</p><h2 id="po-details-title" className="mt-1 text-xl font-bold tracking-tight text-ink">{po.supplier?.legalName ?? 'Unknown supplier'}</h2><div className="mt-3"><PurchaseOrderStatusBadge status={po.status} /></div></div>
          <Button aria-label="Close purchase order details" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </header>
        <div className="flex-1 space-y-6 overflow-y-auto p-4 sm:p-6">
          <section>
            <h3 className="text-sm font-semibold text-ink">Lines</h3>
            <div className="mt-3 space-y-2 sm:hidden">
              {po.lines.map((line) => (
                <div className="rounded-xl border border-border p-3 text-sm" key={line.id}>
                  <p className="font-medium text-ink">{line.productName}</p>
                  <p className="text-xs text-muted">{line.productSku}</p>
                  <dl className="mt-2 grid grid-cols-3 gap-2 text-xs">
                    <div><dt className="text-muted">Qty</dt><dd className="tabular-nums text-ink">{line.orderedQuantity}</dd></div>
                    <div><dt className="text-muted">Unit cost</dt><dd className="tabular-nums text-ink">{line.unitCost}</dd></div>
                    <div><dt className="text-muted">Total</dt><dd className="tabular-nums text-ink">{line.totalAmount}</dd></div>
                  </dl>
                </div>
              ))}
            </div>
            <div className="mt-3 hidden overflow-x-auto rounded-xl border border-border sm:block">
              <table className="w-full min-w-[500px] text-sm">
                <thead className="bg-subtle text-left text-xs font-semibold text-muted"><tr><th className="px-3 py-2">Product</th><th className="px-3 py-2 text-right">Qty</th><th className="px-3 py-2 text-right">Unit cost</th><th className="px-3 py-2 text-right">Total</th></tr></thead>
                <tbody className="divide-y divide-border">
                  {po.lines.map((line) => (
                    <tr key={line.id}>
                      <td className="px-3 py-2"><p className="font-medium text-ink">{line.productName}</p><p className="text-xs text-muted">{line.productSku}</p></td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.orderedQuantity}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.unitCost}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.totalAmount}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <dl className="mt-3 grid grid-cols-2 gap-3 text-sm">
              <Detail label="Subtotal" value={`${po.currencyCode} ${po.subtotalAmount}`} />
              <Detail label="Tax" value={`${po.currencyCode} ${po.taxAmount}`} />
              <Detail label="Discount" value={`${po.currencyCode} ${po.discountAmount}`} />
              <Detail label="Total" value={`${po.currencyCode} ${po.totalAmount}`} />
            </dl>
          </section>
          {po.approvals.length > 0 ? (
            <section>
              <h3 className="text-sm font-semibold text-ink">Approval history</h3>
              <ul className="mt-3 space-y-2">
                {po.approvals.map((approval) => (
                  <li className="rounded-xl border border-border p-3 text-sm" key={approval.id}>
                    <p className="font-medium capitalize text-ink">{approval.decision}</p>
                    {approval.reason ? <p className="mt-1 text-muted">{approval.reason}</p> : null}
                    <p className="mt-1 text-xs text-muted">{new Date(approval.decisionAt).toLocaleString()}</p>
                  </li>
                ))}
              </ul>
            </section>
          ) : null}
          {po.notes ? <section><h3 className="text-sm font-semibold text-ink">Notes</h3><p className="mt-2 whitespace-pre-wrap text-sm text-muted">{po.notes}</p></section> : null}
        </div>
        <footer className="flex flex-wrap gap-2 border-t border-border p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:p-6">
          {po.status === 'draft' && hasPermission('purchase_orders.submit') ? <Button disabled={isActing} onClick={onSubmit}>Submit for approval</Button> : null}
          {po.status === 'submitted' && hasPermission('purchase_orders.approve') ? <Button disabled={isActing} onClick={onApprove}>Approve</Button> : null}
          {po.status === 'submitted' && hasPermission('purchase_orders.approve') ? <Button disabled={isActing} variant="danger" onClick={() => setPrompt('reject')}>Reject</Button> : null}
          {po.status === 'approved' && hasPermission('purchase_orders.order') ? <Button disabled={isActing} onClick={onMarkOrdered}>Mark ordered today</Button> : null}
          {po.status === 'ordered' && hasPermission('purchase_orders.close') ? <Button disabled={isActing} onClick={onClosePo}>Close</Button> : null}
          {canCancel ? <Button disabled={isActing} variant="secondary" onClick={() => setPrompt('cancel')}>Cancel order</Button> : null}
        </footer>
      </aside>
      {prompt === 'reject' ? (
        <ReasonPromptDialog confirmLabel="Reject" description="This reason is recorded in the approval history and the order returns to draft for revision." isSubmitting={isActing} title="Reject purchase order" onClose={() => setPrompt(null)} onConfirm={(reason) => { onReject(reason); setPrompt(null) }} />
      ) : null}
      {prompt === 'cancel' ? (
        <ReasonPromptDialog confirmLabel="Cancel order" description="This purchase order will be cancelled and cannot be reopened." isSubmitting={isActing} title="Cancel purchase order" onClose={() => setPrompt(null)} onConfirm={(reason) => { onCancel(reason); setPrompt(null) }} />
      ) : null}
    </div>
    </Portal>
  )
}

function Detail({ label, value }: { label: string; value: string }) {
  return <div><dt className="text-xs font-semibold uppercase tracking-wide text-muted">{label}</dt><dd className="mt-1 text-sm text-ink">{value}</dd></div>
}
