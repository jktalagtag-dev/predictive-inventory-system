import { useState } from 'react'
import { X } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthProvider'
import type { PaymentMethod, Sale } from '@/features/sales/types/sale'
import { ReasonPromptDialog } from '@/features/sales/components/ReasonPromptDialog'
import { RefundDialog } from '@/features/sales/components/RefundDialog'
import { SaleStatusBadge } from '@/features/sales/components/SaleStatusBadge'
import { Button } from '@/shared/components/Button'
import { drawerPanelClass } from '@/shared/lib/modalClasses'

type SaleDetailsDrawerProps = {
  sale: Sale
  isActing: boolean
  onClose: () => void
  onVoid: (reason: string) => void
  onRefund: (reason: string, lines: Array<{ productId: string; quantity: number }>, payments: Array<{ paymentMethod: PaymentMethod; amount: string }>) => void
}

export function SaleDetailsDrawer({ sale, isActing, onClose, onVoid, onRefund }: SaleDetailsDrawerProps) {
  const { hasPermission } = useAuth()
  const [showVoidPrompt, setShowVoidPrompt] = useState(false)
  const [showRefundDialog, setShowRefundDialog] = useState(false)

  const canVoid = sale.status === 'completed' && hasPermission('sales.void')
  const canRefund = sale.status === 'completed' && hasPermission('sales.refund')

  return (
    <div className="fixed inset-0 z-40 bg-slate-950/30" role="presentation" onMouseDown={onClose}>
      <aside aria-labelledby="sale-details-title" aria-modal="true" className={drawerPanelClass()} role="dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="flex items-start justify-between gap-4 border-b border-border p-4 sm:p-6">
          <div>
            <p className="font-mono text-xs text-muted">{sale.saleNumber}</p>
            <h2 id="sale-details-title" className="mt-1 text-xl font-bold tracking-tight text-ink">{sale.totalAmount} {sale.currencyCode}</h2>
            <div className="mt-3"><SaleStatusBadge status={sale.status} /></div>
          </div>
          <Button aria-label="Close sale details" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </header>
        <div className="flex-1 space-y-6 overflow-y-auto p-4 sm:p-6">
          {sale.reversesSaleId ? <p className="text-sm text-muted">Refund of sale <span className="font-mono">{sale.reversesSaleId}</span>.</p> : null}
          <section>
            <h3 className="text-sm font-semibold text-ink">Lines</h3>
            <div className="mt-3 space-y-2 sm:hidden">
              {sale.lines.map((line) => (
                <div className="rounded-xl border border-border p-3 text-sm" key={line.id}>
                  <p className="font-medium text-ink">{line.productName}</p>
                  <p className="text-xs text-muted">{line.productSku}</p>
                  {line.overrideReason ? <p className="mt-1 text-xs text-warning-text">Override: {line.overrideReason}</p> : null}
                  <dl className="mt-2 grid grid-cols-4 gap-2 text-xs">
                    <div><dt className="text-muted">Qty</dt><dd className="tabular-nums text-ink">{line.quantity}</dd></div>
                    <div><dt className="text-muted">Price</dt><dd className="tabular-nums text-ink">{line.unitPrice}</dd></div>
                    <div><dt className="text-muted">Tax</dt><dd className="tabular-nums text-ink">{line.taxAmount}</dd></div>
                    <div><dt className="text-muted">Total</dt><dd className="tabular-nums font-semibold text-ink">{line.lineTotalAmount}</dd></div>
                  </dl>
                </div>
              ))}
            </div>
            <div className="mt-3 hidden overflow-x-auto rounded-xl border border-border sm:block">
              <table className="w-full min-w-[550px] text-sm">
                <thead className="bg-subtle text-left text-xs font-semibold text-muted"><tr><th className="px-3 py-2">Product</th><th className="px-3 py-2 text-right">Qty</th><th className="px-3 py-2 text-right">Price</th><th className="px-3 py-2 text-right">Tax</th><th className="px-3 py-2 text-right">Total</th></tr></thead>
                <tbody className="divide-y divide-border">
                  {sale.lines.map((line) => (
                    <tr key={line.id}>
                      <td className="px-3 py-2"><p className="font-medium text-ink">{line.productName}</p><p className="text-xs text-muted">{line.productSku}</p>{line.overrideReason ? <p className="mt-1 text-xs text-warning-text">Override: {line.overrideReason}</p> : null}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.quantity}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.unitPrice}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.taxAmount}</td>
                      <td className="px-3 py-2 text-right tabular-nums font-semibold">{line.lineTotalAmount}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
          <section>
            <h3 className="text-sm font-semibold text-ink">Payments</h3>
            <ul className="mt-3 space-y-1.5 text-sm">
              {sale.payments.map((payment) => (
                <li key={payment.id} className="flex justify-between rounded-xl border border-border px-3 py-2"><span className="capitalize text-ink">{payment.paymentMethod.replace('_', ' ')}</span><span className="tabular-nums font-semibold text-ink">{payment.amount}</span></li>
              ))}
            </ul>
          </section>
          {sale.notes ? <section><h3 className="text-sm font-semibold text-ink">Notes</h3><p className="mt-2 whitespace-pre-wrap text-sm text-muted">{sale.notes}</p></section> : null}
        </div>
        <footer className="flex flex-wrap gap-2 border-t border-border p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:p-6">
          {canRefund ? <Button disabled={isActing} onClick={() => setShowRefundDialog(true)}>Refund</Button> : null}
          {canVoid ? <Button disabled={isActing} variant="danger" onClick={() => setShowVoidPrompt(true)}>Void</Button> : null}
        </footer>
      </aside>
      {showVoidPrompt ? (
        <ReasonPromptDialog
          confirmLabel="Void sale"
          description="This restores the sold stock and cannot be undone."
          isSubmitting={isActing}
          title="Void sale"
          onClose={() => setShowVoidPrompt(false)}
          onConfirm={(reason) => { onVoid(reason); setShowVoidPrompt(false) }}
        />
      ) : null}
      {showRefundDialog ? (
        <RefundDialog
          isSubmitting={isActing}
          sale={sale}
          onClose={() => setShowRefundDialog(false)}
          onConfirm={(reason, lines, payments) => { onRefund(reason, lines, payments); setShowRefundDialog(false) }}
        />
      ) : null}
    </div>
  )
}
