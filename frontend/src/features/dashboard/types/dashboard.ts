export type DashboardMetric = {
  value: string
  label: string
  detail: string
}

export type DashboardKpis = {
  inventoryOnHand: DashboardMetric
  salesToday: DashboardMetric
  lowStockCount: DashboardMetric
  criticalStockCount: DashboardMetric
}

export type LowStockItem = {
  id: string
  productId: string
  productSku: string
  productName: string
  availableQuantity: string
  reorderPointQuantity: string
  severity: 'low' | 'medium' | 'high' | 'critical'
}

export type PendingPurchaseOrderItem = {
  id: string
  poNumber: string
  supplierName: string
  status: string
  totalAmount: string
}

export type RecentSaleItem = {
  id: string
  saleNumber: string
  totalAmount: string
  soldAt: string | null
  cashierName: string | null
}

export type SalesTrendPoint = {
  date: string
  totalAmount: string
  saleCount: number
}

export type ForecastSummary = {
  forecastRunId: string
  modelCode: string
  periodGrain: string
  generatedAt: string | null
  totalProductCount: number
  sufficientHistoryCount: number
  coverageRatio: number
} | null

export type SyncHealth = {
  pendingCount: number
  conflictedCount: number
  rejectedCount: number
  acceptedCount: number
  lastReceivedAt: string | null
}

export type DashboardData = {
  kpis: DashboardKpis
  lowStock: LowStockItem[]
  pendingPurchaseOrders: { count: number; items: PendingPurchaseOrderItem[] }
  recentSales: RecentSaleItem[]
  salesTrend: SalesTrendPoint[]
  forecastSummary: ForecastSummary
  syncHealth: SyncHealth
}

export type DashboardMeta = {
  requestId: string
  branchId: string
  from: string
  to: string
  timezone: string
  currency: string
  generatedAt: string
  freshness: string
}

export type DashboardResponse = {
  data: DashboardData
  meta: DashboardMeta
}
