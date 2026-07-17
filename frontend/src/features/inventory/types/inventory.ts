export type InventoryBalance = {
  id: string
  branchId: string
  product: { id: string; sku: string; name: string } | null
  onHandQuantity: string
  reservedQuantity: string
  availableQuantity: string
  incomingQuantity: string
  lastMovementAt: string | null
  version: number
}

export type InventoryBalanceFilters = {
  branchId: string | null
  availability: 'all' | 'in_stock' | 'out_of_stock'
  search: string
  page: number
  perPage: number
}

export type MovementType = 'receipt' | 'sale' | 'adjustment' | 'return' | 'reservation' | 'release' | 'reversal'

export type InventoryMovement = {
  id: string
  branchId: string
  product: { id: string; sku: string; name: string } | null
  movementType: MovementType
  quantityDelta: string
  onHandAfterQuantity: string | null
  referenceType: string
  referenceId: string
  reversesMovementId: string | null
  effectiveAt: string | null
  postedAt: string | null
  actor: { id: string; displayName: string } | null
  correlationId: string
}

export type InventoryMovementFilters = {
  branchId: string | null
  movementType: MovementType | 'all'
  page: number
  perPage: number
}

export const ADJUSTMENT_STATUSES = ['draft', 'pending_approval', 'posted', 'rejected', 'reversed'] as const
export type AdjustmentStatus = (typeof ADJUSTMENT_STATUSES)[number]

export const ADJUSTMENT_REASON_CODES = ['damage', 'count_correction', 'theft', 'expiry', 'other'] as const
export type AdjustmentReasonCode = (typeof ADJUSTMENT_REASON_CODES)[number]

export type InventoryAdjustmentLine = {
  id: string
  lineNumber: number
  productId: string
  productSku: string
  productName: string
  beforeQuantity: string
  quantityDelta: string
  afterQuantity: string
  unitCost: string | null
  notes: string | null
}

export type InventoryAdjustment = {
  id: string
  branchId: string
  adjustmentNumber: string
  status: AdjustmentStatus
  reasonCode: string
  reasonNote: string | null
  effectiveAt: string | null
  approvedByUserId: string | null
  approvedAt: string | null
  postedAt: string | null
  reversalAdjustmentId: string | null
  lineCount: number | null
  lines: InventoryAdjustmentLine[]
  version: number
}

export type InventoryAdjustmentFilters = {
  branchId: string | null
  status: AdjustmentStatus | 'all'
  page: number
  perPage: number
}

export type AdjustmentLineInput = {
  productId: string
  quantityDelta: string
  unitCost: string
  notes: string
}

export type AdjustmentFormValues = {
  reasonCode: AdjustmentReasonCode
  reasonNote: string
  effectiveAt: string
  lines: AdjustmentLineInput[]
}

export type Paginated<T> = { data: T[]; meta: { page: number; perPage: number; total: number } }
