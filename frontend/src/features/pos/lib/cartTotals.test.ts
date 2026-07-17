import { describe, expect, it } from 'vitest'
import { computeCartTotals, computeLineTotals, isLineDiscounted, isLinePriceOverridden, lineRequiresOverrideReason } from '@/features/pos/lib/cartTotals'
import type { CartLine } from '@/features/pos/types/pos'

function makeLine(overrides: Partial<CartLine> = {}): CartLine {
  return {
    productId: '1',
    productUnitId: '1',
    sku: 'SHX-FLT-010',
    name: 'Filter',
    productType: 'stock',
    quantity: 2,
    catalogUnitPrice: '100.0000',
    overriddenUnitPrice: null,
    discountAmount: '0',
    overrideReason: '',
    taxRate: '12.0000',
    availableQuantity: '10.0000',
    ...overrides,
  }
}

describe('computeLineTotals', () => {
  it('computes gross, net, tax, and total for a plain line', () => {
    const totals = computeLineTotals(makeLine())
    expect(totals.grossAmount).toBe(200)
    expect(totals.netAmount).toBe(200)
    expect(totals.taxAmount).toBeCloseTo(24)
    expect(totals.totalAmount).toBeCloseTo(224)
  })

  it('applies a discount before computing tax', () => {
    const totals = computeLineTotals(makeLine({ discountAmount: '20' }))
    expect(totals.netAmount).toBe(180)
    expect(totals.taxAmount).toBeCloseTo(21.6)
    expect(totals.totalAmount).toBeCloseTo(201.6)
  })

  it('uses the overridden price when present', () => {
    const totals = computeLineTotals(makeLine({ overriddenUnitPrice: '80.0000' }))
    expect(totals.grossAmount).toBe(160)
  })

  it('never produces a negative net amount', () => {
    const totals = computeLineTotals(makeLine({ discountAmount: '999' }))
    expect(totals.netAmount).toBe(0)
  })
})

describe('computeCartTotals', () => {
  it('sums totals across multiple lines', () => {
    const totals = computeCartTotals([makeLine(), makeLine({ productId: '2', quantity: 1, catalogUnitPrice: '50.0000' })])
    expect(totals.subtotal).toBe(250)
    expect(totals.tax).toBeCloseTo(30)
    expect(totals.total).toBeCloseTo(280)
  })

  it('returns zeroed totals for an empty cart', () => {
    expect(computeCartTotals([])).toEqual({ subtotal: 0, discount: 0, tax: 0, total: 0 })
  })
})

describe('override detection', () => {
  it('flags a price override only when it differs from catalog price', () => {
    expect(isLinePriceOverridden(makeLine())).toBe(false)
    expect(isLinePriceOverridden(makeLine({ overriddenUnitPrice: '100.0000' }))).toBe(false)
    expect(isLinePriceOverridden(makeLine({ overriddenUnitPrice: '90.0000' }))).toBe(true)
  })

  it('flags a discount only when positive', () => {
    expect(isLineDiscounted(makeLine())).toBe(false)
    expect(isLineDiscounted(makeLine({ discountAmount: '0' }))).toBe(false)
    expect(isLineDiscounted(makeLine({ discountAmount: '5' }))).toBe(true)
  })

  it('requires an override reason for either a price override or a discount', () => {
    expect(lineRequiresOverrideReason(makeLine())).toBe(false)
    expect(lineRequiresOverrideReason(makeLine({ overriddenUnitPrice: '90.0000' }))).toBe(true)
    expect(lineRequiresOverrideReason(makeLine({ discountAmount: '10' }))).toBe(true)
  })
})
