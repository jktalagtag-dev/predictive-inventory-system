import type { PaymentMethod } from '@/features/sales/types/sale'

export type PosProduct = {
  id: string
  sku: string
  barcode: string | null
  name: string
  stockUnit: { id: string; code: string; symbol: string } | null
  productType: 'stock' | 'non_stock' | 'service'
  defaultTaxRate: string
  sellingPrice: string
  stock: { onHandQuantity: string; availableQuantity: string; incomingQuantity: string; lastMovementAt: string | null } | null
}

export type CartLine = {
  productId: string
  productUnitId: string
  sku: string
  name: string
  productType: PosProduct['productType']
  quantity: number
  catalogUnitPrice: string
  overriddenUnitPrice: string | null
  discountAmount: string
  overrideReason: string
  taxRate: string
  availableQuantity: string | null
}

export type CartPayment = {
  localId: string
  paymentMethod: PaymentMethod
  amount: string
  externalReference: string
}

export type FinalizeSalePayload = {
  branchId: string
  soldAt: string
  currencyCode: string
  notes?: string
  approvedByUserId?: string
  lines: Array<{
    productId: string
    productUnitId: string
    quantity: number
    unitPrice?: string
    discountAmount?: string
    overrideReason?: string
  }>
  payments: Array<{ paymentMethod: PaymentMethod; amount: string; externalReference?: string }>
}
