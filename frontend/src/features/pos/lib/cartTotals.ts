import type { CartLine } from '@/features/pos/types/pos'

export type LineTotals = { grossAmount: number; netAmount: number; taxAmount: number; totalAmount: number }

export type CartTotals = { subtotal: number; discount: number; tax: number; total: number }

/**
 * Client-side preview only — the server always recalculates price, tax,
 * and discount authoritatively at finalization (CLAUDE.md section 26/43).
 * Uses floating-point math because this never persists; bcmath-equivalent
 * precision belongs to SaleService.
 */
export function effectiveUnitPrice(line: Pick<CartLine, 'catalogUnitPrice' | 'overriddenUnitPrice'>): string {
  return line.overriddenUnitPrice ?? line.catalogUnitPrice
}

export function computeLineTotals(line: Pick<CartLine, 'quantity' | 'catalogUnitPrice' | 'overriddenUnitPrice' | 'discountAmount' | 'taxRate'>): LineTotals {
  const unitPrice = Number(effectiveUnitPrice(line))
  const discount = Number(line.discountAmount || '0')
  const taxRate = Number(line.taxRate)

  const grossAmount = line.quantity * unitPrice
  const netAmount = Math.max(0, grossAmount - discount)
  const taxAmount = netAmount * (taxRate / 100)
  const totalAmount = netAmount + taxAmount

  return { grossAmount, netAmount, taxAmount, totalAmount }
}

export function computeCartTotals(lines: CartLine[]): CartTotals {
  return lines.reduce<CartTotals>(
    (acc, line) => {
      const totals = computeLineTotals(line)
      return {
        subtotal: acc.subtotal + totals.grossAmount,
        discount: acc.discount + Number(line.discountAmount || '0'),
        tax: acc.tax + totals.taxAmount,
        total: acc.total + totals.totalAmount,
      }
    },
    { subtotal: 0, discount: 0, tax: 0, total: 0 },
  )
}

export function isLinePriceOverridden(line: Pick<CartLine, 'catalogUnitPrice' | 'overriddenUnitPrice'>): boolean {
  return line.overriddenUnitPrice !== null && line.overriddenUnitPrice !== line.catalogUnitPrice
}

export function isLineDiscounted(line: Pick<CartLine, 'discountAmount'>): boolean {
  return Number(line.discountAmount || '0') > 0
}

export function lineRequiresOverrideReason(line: Pick<CartLine, 'catalogUnitPrice' | 'overriddenUnitPrice' | 'discountAmount'>): boolean {
  return isLinePriceOverridden(line) || isLineDiscounted(line)
}
