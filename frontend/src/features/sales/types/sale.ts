export type PaymentMethod = 'cash' | 'card' | 'bank_transfer' | 'ewallet' | 'other'

export const PAYMENT_METHODS: { value: PaymentMethod; label: string }[] = [
  { value: 'cash', label: 'Cash' },
  { value: 'card', label: 'Card' },
  { value: 'bank_transfer', label: 'Bank transfer' },
  { value: 'ewallet', label: 'E-wallet' },
  { value: 'other', label: 'Other' },
]

export type SaleStatus = 'completed' | 'voided' | 'refunded'

export type SaleLine = {
  id: string
  lineNumber: number
  productId: string
  productSku: string
  productName: string
  unitId: string
  quantity: string
  stockQuantityDelta: string
  unitPrice: string
  discountAmount: string
  taxRate: string
  taxAmount: string
  lineTotalAmount: string
  overrideReason: string | null
}

export type SalePayment = {
  id: string
  paymentMethod: PaymentMethod
  amount: string
  currencyCode: string
  externalReference: string | null
  receivedAt: string | null
}

export type Sale = {
  id: string
  branchId: string
  saleNumber: string
  status: SaleStatus
  currencyCode: string
  soldAt: string | null
  completedAt: string | null
  voidedAt: string | null
  refundedAt: string | null
  reversesSaleId: string | null
  subtotalAmount: string
  discountAmount: string
  taxAmount: string
  totalAmount: string
  cashierUserId: string
  cashierName: string | null
  approvedByUserId: string | null
  notes: string | null
  lineCount: number | null
  lines: SaleLine[]
  payments: SalePayment[]
  version: number
}

export type SaleFilters = {
  branchId: string | null
  status: SaleStatus | 'all'
  saleNumber: string
  page: number
  perPage: number
}

export type PaginatedSales = {
  data: Sale[]
  meta: { page: number; perPage: number; total: number }
}

export type RefundLineInput = { productId: string; quantity: number }

export type RefundPaymentInput = { paymentMethod: PaymentMethod; amount: string; externalReference?: string }
