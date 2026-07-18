import type { CartTotals } from '@/features/pos/lib/cartTotals'
import { Button } from '@/shared/components/Button'

type CheckoutSummaryProps = {
  totals: CartTotals
  paymentsTotal: number
  isValid: boolean
  isSubmitting: boolean
  disabledReason: string | null
  onFinalize: () => void
}

export function CheckoutSummary({ totals, paymentsTotal, isValid, isSubmitting, disabledReason, onFinalize }: CheckoutSummaryProps) {
  const paymentMismatch = Math.abs(paymentsTotal - totals.total) > 0.01

  return (
    <section aria-label="Checkout summary" className="space-y-3 rounded-card border border-border bg-surface p-6 shadow-panel">
      <dl className="space-y-1.5 text-sm">
        <div className="flex justify-between text-muted"><dt>Subtotal</dt><dd className="tabular-nums">{totals.subtotal.toFixed(2)}</dd></div>
        <div className="flex justify-between text-muted"><dt>Discount</dt><dd className="tabular-nums">-{totals.discount.toFixed(2)}</dd></div>
        <div className="flex justify-between text-muted"><dt>Tax</dt><dd className="tabular-nums">{totals.tax.toFixed(2)}</dd></div>
        <div className="flex justify-between border-t border-border pt-1.5 text-base font-bold text-ink"><dt>Total</dt><dd className="tabular-nums">{totals.total.toFixed(2)}</dd></div>
        <div className={`flex justify-between ${paymentMismatch ? 'font-semibold text-danger-text' : 'text-muted'}`}><dt>Payments entered</dt><dd className="tabular-nums">{paymentsTotal.toFixed(2)}</dd></div>
      </dl>
      {disabledReason ? <p className="text-xs text-muted">{disabledReason}</p> : null}
      <Button className="w-full justify-center" disabled={!isValid || isSubmitting} type="button" onClick={onFinalize}>
        {isSubmitting ? 'Finalizing…' : 'Finalize sale'}
      </Button>
    </section>
  )
}
