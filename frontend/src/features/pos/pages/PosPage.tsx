import { useEffect, useState } from 'react'
import { useAuth } from '@/features/auth/AuthProvider'
import { useFinalizeSale } from '@/features/pos/hooks/usePos'
import { computeCartTotals } from '@/features/pos/lib/cartTotals'
import { usePosCartStore } from '@/features/pos/state/posCartStore'
import { CartTable } from '@/features/pos/components/CartTable'
import { CheckoutSummary } from '@/features/pos/components/CheckoutSummary'
import { PaymentsPanel } from '@/features/pos/components/PaymentsPanel'
import { ProductSearchPanel } from '@/features/pos/components/ProductSearchPanel'
import { lineRequiresOverrideReason } from '@/features/pos/lib/cartTotals'
import { type ApiError } from '@/shared/api/client'
import { PageHeader } from '@/shared/components/PageHeader'

export default function PosPage() {
  const { session, hasPermission } = useAuth()
  const cart = usePosCartStore()
  const finalizeMutation = useFinalizeSale()
  const [receiptSaleNumber, setReceiptSaleNumber] = useState<string | null>(null)

  const defaultBranchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id ?? null
  useEffect(() => {
    if (defaultBranchId) cart.setBranch(defaultBranchId)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [defaultBranchId])

  const canOverridePrice = hasPermission('pos.price_override')
  const canOverrideDiscount = hasPermission('pos.discount_override')

  const totals = computeCartTotals(cart.lines)
  const paymentsTotal = cart.payments.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0)
  const hasOverStockLine = cart.lines.some((line) => line.availableQuantity !== null && line.quantity > Number(line.availableQuantity))
  const missingOverrideReason = cart.lines.some((line) => lineRequiresOverrideReason(line) && line.overrideReason.trim() === '')
  const paymentsCoverTotal = cart.payments.length > 0 && Math.abs(paymentsTotal - totals.total) <= 0.01

  let disabledReason: string | null = null
  if (cart.lines.length === 0) disabledReason = 'Add at least one product to the cart.'
  else if (hasOverStockLine) disabledReason = 'One or more lines exceed available stock.'
  else if (missingOverrideReason) disabledReason = 'Provide a reason for each overridden price or discount.'
  else if (!paymentsCoverTotal) disabledReason = 'Payments must add up to the sale total.'

  const isValid = disabledReason === null

  const finalize = () => {
    if (!cart.branchId) return

    finalizeMutation.mutate(
      {
        branchId: cart.branchId,
        soldAt: new Date().toISOString(),
        currencyCode: 'PHP',
        lines: cart.lines.map((line) => ({
          productId: line.productId,
          productUnitId: line.productUnitId,
          quantity: line.quantity,
          unitPrice: line.overriddenUnitPrice ?? undefined,
          discountAmount: Number(line.discountAmount) > 0 ? line.discountAmount : undefined,
          overrideReason: lineRequiresOverrideReason(line) ? line.overrideReason : undefined,
        })),
        payments: cart.payments.map((payment) => ({
          paymentMethod: payment.paymentMethod,
          amount: payment.amount,
          externalReference: payment.externalReference || undefined,
        })),
      },
      {
        onSuccess: (sale) => {
          setReceiptSaleNumber(sale.saleNumber)
          cart.clear()
        },
      },
    )
  }

  const error = finalizeMutation.error as ApiError | null

  return (
    <div className="flex flex-col gap-4 lg:h-[calc(100dvh-8rem)]">
      <PageHeader description="Search or scan a product, review the cart, then finalize the sale." title="Point of Sale" />

      {receiptSaleNumber ? (
        <div className="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success-text" role="status">
          <span>Sale {receiptSaleNumber} completed.</span>
          <button className="font-semibold underline" type="button" onClick={() => setReceiptSaleNumber(null)}>Dismiss</button>
        </div>
      ) : null}
      {error ? <div className="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <div className="flex flex-col gap-4 lg:grid lg:flex-1 lg:grid-cols-[380px_1fr_300px] lg:overflow-hidden">
        <ProductSearchPanel branchId={cart.branchId} onAdd={cart.addProduct} />

        <div className="flex flex-col gap-4 lg:overflow-y-auto">
          <CartTable
            canOverrideDiscount={canOverrideDiscount}
            canOverridePrice={canOverridePrice}
            lines={cart.lines}
            onDiscountChange={cart.setDiscount}
            onPriceChange={cart.setOverriddenPrice}
            onQuantityChange={cart.setQuantity}
            onReasonChange={cart.setOverrideReason}
            onRemove={cart.removeLine}
          />
        </div>

        <div className="flex flex-col gap-4 lg:overflow-y-auto">
          <PaymentsPanel payments={cart.payments} onAdd={cart.addPayment} onRemove={cart.removePayment} onUpdate={cart.updatePayment} />
          <div className="hidden lg:block">
            <CheckoutSummary
              disabledReason={disabledReason}
              isSubmitting={finalizeMutation.isPending}
              isValid={isValid}
              paymentsTotal={paymentsTotal}
              totals={totals}
              onFinalize={finalize}
            />
          </div>
        </div>
      </div>

      <div className="sticky bottom-[calc(4rem+env(safe-area-inset-bottom))] z-30 lg:hidden">
        <CheckoutSummary
          disabledReason={disabledReason}
          isSubmitting={finalizeMutation.isPending}
          isValid={isValid}
          paymentsTotal={paymentsTotal}
          totals={totals}
          onFinalize={finalize}
        />
      </div>
    </div>
  )
}
