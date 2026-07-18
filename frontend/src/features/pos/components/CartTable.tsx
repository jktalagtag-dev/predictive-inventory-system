import { Trash2 } from 'lucide-react'
import { computeLineTotals, lineRequiresOverrideReason } from '@/features/pos/lib/cartTotals'
import type { CartLine } from '@/features/pos/types/pos'
import { Button } from '@/shared/components/Button'

type CartTableProps = {
  lines: CartLine[]
  canOverridePrice: boolean
  canOverrideDiscount: boolean
  onQuantityChange: (productId: string, quantity: number) => void
  onPriceChange: (productId: string, price: string | null) => void
  onDiscountChange: (productId: string, discountAmount: string) => void
  onReasonChange: (productId: string, reason: string) => void
  onRemove: (productId: string) => void
}

export function CartTable({ lines, canOverridePrice, canOverrideDiscount, onQuantityChange, onPriceChange, onDiscountChange, onReasonChange, onRemove }: CartTableProps) {
  if (lines.length === 0) {
    return <div className="flex h-full items-center justify-center rounded-card border border-dashed border-border p-8 text-center text-sm text-muted">Cart is empty. Search for a product to begin.</div>
  }

  return (
    <>
      <div className="space-y-3 md:hidden">
        {lines.map((line) => {
          const totals = computeLineTotals(line)
          const overQty = line.availableQuantity !== null && line.quantity > Number(line.availableQuantity)
          const needsReason = lineRequiresOverrideReason(line)
          return (
            <div className="rounded-card border border-border bg-surface p-4 shadow-panel" key={line.productId}>
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="truncate font-medium text-ink">{line.name}</p>
                  <p className="font-mono text-xs text-muted">{line.sku}</p>
                </div>
                <Button aria-label={`Remove ${line.name}`} size="icon" variant="ghost" onClick={() => onRemove(line.productId)}><Trash2 aria-hidden="true" size={16} /></Button>
              </div>
              {overQty ? <p className="mt-1 text-xs font-medium text-danger-text">Exceeds available stock ({line.availableQuantity})</p> : null}
              {needsReason ? (
                <input
                  className="mt-2 h-11 w-full rounded-lg border border-warning/40 bg-warning/10 px-2 text-xs outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600/30"
                  placeholder="Override reason (required)"
                  value={line.overrideReason}
                  onChange={(event) => onReasonChange(line.productId, event.target.value)}
                />
              ) : null}
              <div className="mt-3 grid grid-cols-3 gap-2">
                <label className="text-xs font-semibold text-muted">Qty
                  <input
                    className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-right text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
                    min="1"
                    step="1"
                    type="number"
                    value={line.quantity}
                    onChange={(event) => onQuantityChange(line.productId, Number(event.target.value))}
                  />
                </label>
                <label className="text-xs font-semibold text-muted">Price
                  <input
                    className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-right text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 disabled:bg-subtle disabled:text-muted"
                    disabled={!canOverridePrice}
                    step="0.0001"
                    type="number"
                    value={line.overriddenUnitPrice ?? line.catalogUnitPrice}
                    onChange={(event) => onPriceChange(line.productId, event.target.value === '' ? null : event.target.value)}
                  />
                </label>
                <label className="text-xs font-semibold text-muted">Discount
                  <input
                    className="mt-1 h-11 w-full rounded-lg border border-border bg-surface px-2 text-right text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 disabled:bg-subtle disabled:text-muted"
                    disabled={!canOverrideDiscount}
                    min="0"
                    step="0.0001"
                    type="number"
                    value={line.discountAmount}
                    onChange={(event) => onDiscountChange(line.productId, event.target.value)}
                  />
                </label>
              </div>
              <div className="mt-2 flex justify-between border-t border-border pt-2 text-sm">
                <span className="text-muted">Line total</span>
                <span className="font-semibold tabular-nums text-ink">{totals.totalAmount.toFixed(2)}</span>
              </div>
            </div>
          )
        })}
      </div>

      <div className="hidden overflow-x-auto rounded-card border border-border bg-surface shadow-panel md:block">
        <table className="w-full min-w-[720px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr>
              <th className="px-4 py-2.5">Product</th>
              <th className="w-24 px-3 py-2.5 text-right">Qty</th>
              <th className="w-32 px-3 py-2.5 text-right">Price</th>
              <th className="w-32 px-3 py-2.5 text-right">Discount</th>
              <th className="px-3 py-2.5 text-right">Total</th>
              <th className="w-10 px-3 py-2.5" />
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {lines.map((line) => {
              const totals = computeLineTotals(line)
              const overQty = line.availableQuantity !== null && line.quantity > Number(line.availableQuantity)
              const needsReason = lineRequiresOverrideReason(line)
              return (
                <tr key={line.productId}>
                  <td className="px-4 py-3 align-top">
                    <p className="font-medium text-ink">{line.name}</p>
                    <p className="font-mono text-xs text-muted">{line.sku}</p>
                    {overQty ? <p className="mt-1 text-xs font-medium text-danger-text">Exceeds available stock ({line.availableQuantity})</p> : null}
                    {needsReason ? (
                      <input
                        className="mt-2 h-8 w-full rounded-lg border border-warning/40 bg-warning/10 px-2 text-xs outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600/30"
                        placeholder="Override reason (required)"
                        value={line.overrideReason}
                        onChange={(event) => onReasonChange(line.productId, event.target.value)}
                      />
                    ) : null}
                  </td>
                  <td className="px-3 py-3 text-right align-top">
                    <input
                      className="h-9 w-20 rounded-lg border border-border bg-surface px-2 text-right text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
                      min="1"
                      step="1"
                      type="number"
                      value={line.quantity}
                      onChange={(event) => onQuantityChange(line.productId, Number(event.target.value))}
                    />
                  </td>
                  <td className="px-3 py-3 text-right align-top">
                    <input
                      className="h-9 w-28 rounded-lg border border-border bg-surface px-2 text-right text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 disabled:bg-subtle disabled:text-muted"
                      disabled={!canOverridePrice}
                      step="0.0001"
                      type="number"
                      value={line.overriddenUnitPrice ?? line.catalogUnitPrice}
                      onChange={(event) => onPriceChange(line.productId, event.target.value === '' ? null : event.target.value)}
                    />
                  </td>
                  <td className="px-3 py-3 text-right align-top">
                    <input
                      className="h-9 w-24 rounded-lg border border-border bg-surface px-2 text-right text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 disabled:bg-subtle disabled:text-muted"
                      disabled={!canOverrideDiscount}
                      min="0"
                      step="0.0001"
                      type="number"
                      value={line.discountAmount}
                      onChange={(event) => onDiscountChange(line.productId, event.target.value)}
                    />
                  </td>
                  <td className="px-3 py-3 text-right align-top font-semibold tabular-nums text-ink">{totals.totalAmount.toFixed(2)}</td>
                  <td className="px-3 py-3 text-right align-top">
                    <Button aria-label={`Remove ${line.name}`} size="icon" variant="ghost" onClick={() => onRemove(line.productId)}><Trash2 aria-hidden="true" size={16} /></Button>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
    </>
  )
}
