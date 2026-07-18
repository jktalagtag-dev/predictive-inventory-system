import { Plus, Trash2 } from 'lucide-react'
import { PAYMENT_METHODS } from '@/features/sales/types/sale'
import type { CartPayment } from '@/features/pos/types/pos'
import { Button } from '@/shared/components/Button'

type PaymentsPanelProps = {
  payments: CartPayment[]
  onAdd: () => void
  onUpdate: (localId: string, patch: Partial<Omit<CartPayment, 'localId'>>) => void
  onRemove: (localId: string) => void
}

export function PaymentsPanel({ payments, onAdd, onUpdate, onRemove }: PaymentsPanelProps) {
  return (
    <section aria-label="Payments" className="space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold text-ink">Payments</h3>
        <Button type="button" variant="secondary" onClick={onAdd}><Plus aria-hidden="true" size={15} /> Add payment</Button>
      </div>
      {payments.length === 0 ? (
        <p className="text-sm text-muted">Add at least one payment covering the sale total.</p>
      ) : (
        <ul className="space-y-2">
          {payments.map((payment) => (
            <li key={payment.localId} className="flex items-center gap-2">
              <select
                className="h-11 flex-1 rounded-xl border border-border bg-surface px-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
                value={payment.paymentMethod}
                onChange={(event) => onUpdate(payment.localId, { paymentMethod: event.target.value as CartPayment['paymentMethod'] })}
              >
                {PAYMENT_METHODS.map((method) => <option key={method.value} value={method.value}>{method.label}</option>)}
              </select>
              <input
                className="h-11 w-28 rounded-xl border border-border bg-surface px-2 text-right text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
                min="0"
                placeholder="Amount"
                step="0.01"
                type="number"
                value={payment.amount}
                onChange={(event) => onUpdate(payment.localId, { amount: event.target.value })}
              />
              <Button aria-label="Remove payment" size="icon" variant="ghost" onClick={() => onRemove(payment.localId)}><Trash2 aria-hidden="true" size={16} /></Button>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
