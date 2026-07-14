export type DashboardMetric = {
  value: string
  label: string
  detail: string
}

export type StockAlert = {
  id: string
  productName: string
  sku: string
  availableQuantity: string
  reorderPoint: string
  status: 'low' | 'critical'
}

export type ActivityItem = {
  id: string
  title: string
  detail: string
  occurredAt: string
  type: 'receipt' | 'sale' | 'adjustment' | 'forecast'
}

export type DashboardData = {
  generatedAt: string
  inventorySummary: DashboardMetric
  salesToday: DashboardMetric
  lowStock: DashboardMetric
  criticalStock: DashboardMetric
  forecastSummary: DashboardMetric
  eoqSummary: DashboardMetric
  stockAlerts: StockAlert[]
  recentActivity: ActivityItem[]
}
