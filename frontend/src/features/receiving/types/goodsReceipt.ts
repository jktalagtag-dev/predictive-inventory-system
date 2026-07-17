export type GoodsReceiptStatus = 'draft' | 'posted' | 'reversed'

export type GoodsReceiptLine = {
  id: string
  lineNumber: number
  purchaseOrderLineId: string | null
  productId: string
  productSku: string
  productName: string
  unitId: string
  receivedQuantity: string
  acceptedQuantity: string
  rejectedQuantity: string
  unitCost: string
  lotNumber: string | null
  serialNumber: string | null
  expiryDate: string | null
  rejectionReason: string | null
  notes: string | null
}

export type GoodsReceipt = {
  id: string
  purchaseOrderId: string | null
  branchId: string
  receiptNumber: string
  status: GoodsReceiptStatus
  supplierDeliveryNumber: string | null
  receivedAt: string | null
  postedAt: string | null
  reversedAt: string | null
  reversalReceiptId: string | null
  notes: string | null
  lineCount: number | null
  lines: GoodsReceiptLine[]
  version: number
}

export type GoodsReceiptFilters = {
  branchId: string | null
  purchaseOrderId: string | 'all'
  status: GoodsReceiptStatus | 'all'
  page: number
  perPage: number
}

export type GoodsReceiptLineInput = {
  purchaseOrderLineId: string
  productSku: string
  productName: string
  remainingQuantity: string
  receivedQuantity: string
  acceptedQuantity: string
  rejectedQuantity: string
  lotNumber: string
  serialNumber: string
  expiryDate: string
  rejectionReason: string
  notes: string
}

export type GoodsReceiptFormValues = {
  purchaseOrderId: string
  supplierDeliveryNumber: string
  receivedAt: string
  notes: string
  lines: GoodsReceiptLineInput[]
}

export type PaginatedGoodsReceipts = {
  data: GoodsReceipt[]
  meta: { page: number; perPage: number; total: number }
}
