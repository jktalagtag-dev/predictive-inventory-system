export type PeriodGrain = 'daily' | 'weekly' | 'monthly'

export type ColdStartStatus = 'sufficient_history' | 'insufficient_history' | 'manual_override'

export type ForecastRunStatus = 'queued' | 'running' | 'completed' | 'failed'

export type ForecastRunItem = {
  productId: string
  productSku: string
  productName: string
  historyPeriodCount: number
  demandTotal: string
  forecastQuantity: string | null
  coldStartStatus: ColdStartStatus
  manualQuantity: string | null
  manualReason: string | null
  manualExpiresAt: string | null
}

export type ForecastRun = {
  id: string
  branchId: string | null
  modelCode: string
  modelVersion: string
  periodGrain: PeriodGrain
  windowPeriods: number
  historyStartDate: string | null
  historyEndDate: string | null
  dataCutoffAt: string | null
  status: ForecastRunStatus
  failureCode: string | null
  itemCount: number | null
  items: ForecastRunItem[]
  createdAt: string | null
}

export type ForecastRunFilters = {
  branchId: string | null
  page: number
  perPage: number
}

export type CreateForecastRunPayload = {
  branchId: string
  modelCode: 'sma'
  periodGrain: PeriodGrain
  windowPeriods: number
  historyStartDate: string
  historyEndDate: string
  productIds?: string[]
}

export type PaginatedForecastRuns = {
  data: ForecastRun[]
  meta: { page: number; perPage: number; total: number }
}
