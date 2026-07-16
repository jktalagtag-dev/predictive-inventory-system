export type PurchaseOrderStatus = 'draft' | 'submitted' | 'approved' | 'ordered' | 'partially_received' | 'received' | 'cancelled' | 'closed'

export type PurchaseOrderLine = {
  id: string
  lineNumber: number
  productId: string
  productSku: string
  productName: string
  unitId: string
  orderedQuantity: string
  receivedQuantity: string
  unitCost: string
  taxRate: string
  discountAmount: string
  netAmount: string
  taxAmount: string
  totalAmount: string
  notes: string | null
}

export type PurchaseOrderApproval = {
  id: string
  approvalStage: number
  decision: 'approved' | 'rejected'
  decisionByUserId: string
  decisionAt: string
  reason: string | null
}

export type PurchaseOrder = {
  id: string
  branchId: string
  supplier: { id: string; code: string; legalName: string } | null
  poNumber: string
  status: PurchaseOrderStatus
  currencyCode: string
  orderedAt: string | null
  expectedReceiptAt: string | null
  submittedAt: string | null
  approvedAt: string | null
  cancelledAt: string | null
  subtotalAmount: string
  taxAmount: string
  discountAmount: string
  totalAmount: string
  supplierReference: string | null
  notes: string | null
  lines: PurchaseOrderLine[]
  approvals: PurchaseOrderApproval[]
  version: number
}

export type PurchaseOrderFilters = {
  branchId: string | null
  supplierId: string | 'all'
  status: PurchaseOrderStatus | 'all'
  search: string
  page: number
  perPage: number
}

export type PurchaseOrderLineInput = {
  productId: string
  unitId: string
  orderedQuantity: string
  unitCost: string
  taxRate: string
  discountAmount: string
}

export type PurchaseOrderFormValues = {
  supplierId: string
  currencyCode: string
  expectedReceiptAt: string
  supplierReference: string
  notes: string
  lines: PurchaseOrderLineInput[]
}

export type PaginatedPurchaseOrders = {
  data: PurchaseOrder[]
  meta: { page: number; perPage: number; total: number }
}
