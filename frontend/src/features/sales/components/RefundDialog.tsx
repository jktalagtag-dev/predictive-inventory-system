import { useMemo, useState } from 'react'
import { Plus, Trash2, X } from 'lucide-react'
import { PAYMENT_METHODS, type PaymentMethod, type Sale } from '@/features/sales/types/sale'
import { Button } from '@/shared/components/Button'

type RefundLineDraft = { productId: string; sku: string; name: string; unitPrice: string; taxRate: string; originalQuantity: string; refundQuantity: string }
type RefundPaymentDraft = { localId: string; paymentMethod: PaymentMethod; amount: string }

type RefundDialogProps = {
  sale: Sale
  isSubmitting: boolean
  onClose: () => void
  onConfirm: (reason: string, lines: Array<{ productId: string; quantity: number }>, payments: Array<{ paymentMethod: PaymentMethod; amount: string }>) => void
}

export function RefundDialog({ sale, isSubmitting, onClose, onConfirm }: RefundDialogProps) {
  const [reason, setReason] = useState('')
  const [lines, setLines] = useState<RefundLineDraft[]>(
    sale.lines.map((line) => ({ productId: line.productId, sku: line.productSku, name: line.productName, unitPrice: line.unitPrice, taxRate: line.taxRate, originalQuantity: line.quantity, refundQuantity: '0' })),
  )
  const [payments, setPayments] = useState<RefundPaymentDraft[]>([])

  const refundTotal = useMemo(
    () => lines.reduce((sum, line) => {
      const qty = Number(line.refundQuantity) || 0
      const gross = qty * Number(line.unitPrice)
      const tax = gross * (Number(line.taxRate) / 100)
      return sum + gross + tax
    }, 0),
    [lines],
  )
  const paymentsTotal = payments.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0)
  const activeLines = lines.filter((line) => Number(line.refundQuantity) > 0)
  const paymentsMatch = payments.length > 0 && Math.abs(paymentsTotal - refundTotal) <= 0.01
  const isValid = activeLines.length > 0 && paymentsMatch && reason.trim() !== ''

  const submit = () => {
    onConfirm(
      reason,
      activeLines.map((line) => ({ productId: line.productId, quantity: Number(line.refundQuantity) })),
      payments.map((payment) => ({ paymentMethod: payment.paymentMethod, amount: payment.amount })),
    )
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation" onMouseDown={(event) => event.stopPropagation()}>
      <section aria-labelledby="refund-dialog-title" aria-modal="true" className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-border bg-surface p-6 shadow-panel" role="dialog">
        <div className="flex items-start justify-between gap-4">
          <div><h2 id="refund-dialog-title" className="text-lg font-bold text-ink">Refund sale {sale.saleNumber}</h2><p className="mt-1 text-sm text-muted">Choose the quantity to refund per line. The server will reject amounts exceeding what remains refundable.</p></div>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>

        <div className="mt-5 overflow-x-auto rounded-lg border border-border">
          <table className="w-full min-w-[500px] text-sm">
            <thead className="bg-subtle text-left text-xs font-semibold text-muted"><tr><th className="px-3 py-2">Product</th><th className="px-3 py-2 text-right">Sold qty</th><th className="px-3 py-2 text-right">Refund qty</th></tr></thead>
            <tbody className="divide-y divide-border">
              {lines.map((line, index) => (
                <tr key={line.productId}>
                  <td className="px-3 py-2"><p className="font-medium text-ink">{line.name}</p><p className="text-xs text-muted">{line.sku}</p></td>
                  <td className="px-3 py-2 text-right tabular-nums text-muted">{line.originalQuantity}</td>
                  <td className="px-3 py-2 text-right">
                    <input
                      className="h-9 w-24 rounded-md border border-border bg-surface px-2 text-right text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
                      max={line.originalQuantity}
                      min="0"
                      step="0.0001"
                      type="number"
                      value={line.refundQuantity}
                      onChange={(event) => setLines((state) => state.map((item, itemIndex) => (itemIndex === index ? { ...item, refundQuantity: event.target.value } : item)))}
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <label className="mt-4 block text-sm font-semibold text-ink">Reason
          <textarea className="mt-2 w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required rows={2} value={reason} onChange={(event) => setReason(event.target.value)} />
        </label>

        <div className="mt-4 space-y-2">
          <div className="flex items-center justify-between"><h3 className="text-sm font-semibold text-ink">Refund payments</h3><Button type="button" variant="secondary" onClick={() => setPayments((state) => [...state, { localId: crypto.randomUUID(), paymentMethod: 'cash', amount: '' }])}><Plus aria-hidden="true" size={15} /> Add</Button></div>
          {payments.map((payment, index) => (
            <div key={payment.localId} className="flex items-center gap-2">
              <select className="h-10 flex-1 rounded-lg border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={payment.paymentMethod} onChange={(event) => setPayments((state) => state.map((item, itemIndex) => (itemIndex === index ? { ...item, paymentMethod: event.target.value as PaymentMethod } : item)))}>
                {PAYMENT_METHODS.map((method) => <option key={method.value} value={method.value}>{method.label}</option>)}
              </select>
              <input className="h-10 w-28 rounded-lg border border-border bg-surface px-2 text-right text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" min="0" step="0.01" type="number" value={payment.amount} onChange={(event) => setPayments((state) => state.map((item, itemIndex) => (itemIndex === index ? { ...item, amount: event.target.value } : item)))} />
              <Button aria-label="Remove payment" size="icon" variant="ghost" onClick={() => setPayments((state) => state.filter((_, itemIndex) => itemIndex !== index))}><Trash2 aria-hidden="true" size={16} /></Button>
            </div>
          ))}
        </div>

        <div className="mt-4 flex items-center justify-between border-t border-border pt-4 text-sm">
          <span className="text-muted">Refund total (advisory, server recalculates)</span>
          <span className="font-semibold tabular-nums text-ink">{refundTotal.toFixed(2)}</span>
        </div>

        <div className="mt-5 flex justify-end gap-3 border-t border-border pt-5">
          <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
          <Button disabled={!isValid || isSubmitting} type="button" variant="danger" onClick={submit}>{isSubmitting ? 'Submitting' : 'Confirm refund'}</Button>
        </div>
      </section>
    </div>
  )
}
