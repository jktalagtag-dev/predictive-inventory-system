export type SafetyStockBasis = 'policy_minimum' | 'service_level' | 'manual_override'

export type LeadTimeBasis = 'supplier' | 'product_default' | 'override'

export type ReorderPolicy = {
  id: string
  branchId: string
  productId: string
  productSku: string | null
  productName: string | null
  preferredSupplierId: string | null
  preferredSupplierName: string | null
  safetyStockQuantity: string
  safetyStockBasis: SafetyStockBasis
  leadTimeDaysOverride: string | null
  leadTimeBasis: LeadTimeBasis
  reorderPointQuantity: string | null
  ropCalculatedAt: string | null
  isActive: boolean
  version: number
}

export type ReorderPolicyFilters = {
  branchId: string | null
  page: number
  perPage: number
}

export type CreateReorderPolicyPayload = {
  branchId: string
  productId: string
  preferredSupplierId?: string
  safetyStockQuantity: string
  safetyStockBasis: SafetyStockBasis
  leadTimeDaysOverride?: string
  leadTimeBasis: LeadTimeBasis
}

export type PaginatedReorderPolicies = {
  data: ReorderPolicy[]
  meta: { page: number; perPage: number; total: number }
}

export type EoqCalculation = {
  id: string
  reorderPolicyId: string
  annualDemandQuantity: string
  orderingCost: string
  annualHoldingCostPerUnit: string
  rawEoqQuantity: string | null
  recommendedOrderQuantity: string | null
  currencyCode: string
  formulaVersion: string
  status: 'valid' | 'invalid_input' | 'superseded'
  invalidReason: string | null
  calculatedAt: string | null
}

export type AlertStatus = 'active' | 'acknowledged' | 'resolved' | 'dismissed'

export type AlertSeverity = 'low' | 'medium' | 'high' | 'critical'

export type RestockingAlertEvent = {
  id: string
  eventType: string
  fromStatus: string | null
  toStatus: string | null
  details: Record<string, unknown> | null
  occurredAt: string | null
}

export type RestockingAlert = {
  id: string
  reorderPolicyId: string
  branchId: string | null
  productId: string | null
  productSku: string | null
  productName: string | null
  status: AlertStatus
  severity: AlertSeverity
  availableQuantitySnapshot: string
  incomingQuantitySnapshot: string
  reorderPointSnapshot: string
  recommendedOrderQuantity: string | null
  firstTriggeredAt: string | null
  lastEvaluatedAt: string | null
  resolvedAt: string | null
  dismissalReason: string | null
  assignedToUserId: string | null
  events: RestockingAlertEvent[]
  version: number
}

export type RestockingAlertFilters = {
  branchId: string | null
  status: AlertStatus | 'all'
  severity: AlertSeverity | 'all'
  page: number
  perPage: number
}

export type PaginatedRestockingAlerts = {
  data: RestockingAlert[]
  meta: { page: number; perPage: number; total: number }
}
