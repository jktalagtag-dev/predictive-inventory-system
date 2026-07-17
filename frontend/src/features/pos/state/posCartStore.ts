import { create } from 'zustand'
import type { CartLine, CartPayment, PosProduct } from '@/features/pos/types/pos'
import type { PaymentMethod } from '@/features/sales/types/sale'

type PosCartState = {
  branchId: string | null
  lines: CartLine[]
  payments: CartPayment[]
  setBranch: (branchId: string) => void
  addProduct: (product: PosProduct) => void
  setQuantity: (productId: string, quantity: number) => void
  setOverriddenPrice: (productId: string, price: string | null) => void
  setDiscount: (productId: string, discountAmount: string) => void
  setOverrideReason: (productId: string, reason: string) => void
  removeLine: (productId: string) => void
  addPayment: () => void
  updatePayment: (localId: string, patch: Partial<Omit<CartPayment, 'localId'>>) => void
  removePayment: (localId: string) => void
  clear: () => void
}

/**
 * POS cart draft, per CLAUDE.md section 24 ("Split stores by bounded
 * purpose such as ... POS cart draft"). Deliberately not persisted: a
 * reloaded cart would carry stale prices and stock advisories that the
 * server must re-validate anyway at finalization, so nothing is gained by
 * surviving a refresh and it would violate section 22's "never use client
 * state as the authority for inventory availability" guidance if treated
 * as durable.
 */
export const usePosCartStore = create<PosCartState>()((set, get) => ({
  branchId: null,
  lines: [],
  payments: [],

  setBranch: (branchId) => set((state) => (state.branchId === branchId ? state : { branchId, lines: [], payments: [] })),

  addProduct: (product) => {
    if (!product.stockUnit) return
    const existing = get().lines.find((line) => line.productId === product.id)
    if (existing) {
      set((state) => ({ lines: state.lines.map((line) => (line.productId === product.id ? { ...line, quantity: line.quantity + 1 } : line)) }))
      return
    }
    const newLine: CartLine = {
      productId: product.id,
      productUnitId: product.stockUnit.id,
      sku: product.sku,
      name: product.name,
      productType: product.productType,
      quantity: 1,
      catalogUnitPrice: product.sellingPrice,
      overriddenUnitPrice: null,
      discountAmount: '0',
      overrideReason: '',
      taxRate: product.defaultTaxRate,
      availableQuantity: product.stock?.availableQuantity ?? null,
    }
    set((state) => ({ lines: [...state.lines, newLine] }))
  },

  setQuantity: (productId, quantity) =>
    set((state) => ({ lines: state.lines.map((line) => (line.productId === productId ? { ...line, quantity: Math.max(1, quantity) } : line)) })),

  setOverriddenPrice: (productId, price) =>
    set((state) => ({ lines: state.lines.map((line) => (line.productId === productId ? { ...line, overriddenUnitPrice: price } : line)) })),

  setDiscount: (productId, discountAmount) =>
    set((state) => ({ lines: state.lines.map((line) => (line.productId === productId ? { ...line, discountAmount } : line)) })),

  setOverrideReason: (productId, overrideReason) =>
    set((state) => ({ lines: state.lines.map((line) => (line.productId === productId ? { ...line, overrideReason } : line)) })),

  removeLine: (productId) => set((state) => ({ lines: state.lines.filter((line) => line.productId !== productId) })),

  addPayment: () =>
    set((state) => ({
      payments: [...state.payments, { localId: crypto.randomUUID(), paymentMethod: 'cash' as PaymentMethod, amount: '', externalReference: '' }],
    })),

  updatePayment: (localId, patch) =>
    set((state) => ({ payments: state.payments.map((payment) => (payment.localId === localId ? { ...payment, ...patch } : payment)) })),

  removePayment: (localId) => set((state) => ({ payments: state.payments.filter((payment) => payment.localId !== localId) })),

  clear: () => set((state) => ({ lines: [], payments: [], branchId: state.branchId })),
}))
