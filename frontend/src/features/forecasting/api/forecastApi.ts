import { apiClient } from '@/shared/api/client'
import type { CreateForecastRunPayload, ForecastRun, ForecastRunFilters, PaginatedForecastRuns } from '@/features/forecasting/types/forecast'

type ApiEnvelope<T> = { data: T; meta?: PaginatedForecastRuns['meta'] }

export const forecastQueryKeys = {
  lists: () => ['forecast-runs'] as const,
  list: (filters: ForecastRunFilters) => ['forecast-runs', filters] as const,
  detail: (id: string) => ['forecast-runs', 'detail', id] as const,
}

export async function getForecastRuns(filters: ForecastRunFilters): Promise<PaginatedForecastRuns> {
  const response = await apiClient.get<ApiEnvelope<ForecastRun[]>>('/forecast-runs', {
    params: { branchId: filters.branchId ?? undefined, page: filters.page, perPage: filters.perPage },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function getForecastRun(id: string): Promise<ForecastRun> {
  const response = await apiClient.get<ApiEnvelope<ForecastRun>>(`/forecast-runs/${id}`)
  return response.data.data
}

export async function createForecastRun(payload: CreateForecastRunPayload): Promise<ForecastRun> {
  const response = await apiClient.post<ApiEnvelope<ForecastRun>>('/forecast-runs', payload)
  return response.data.data
}

export async function recordManualPlan(runId: string, productId: string, manualQuantity: string, reason: string, expiresAt: string): Promise<void> {
  await apiClient.post(`/forecast-runs/${runId}/items/${productId}/manual-plan`, { manualQuantity, reason, expiresAt })
}
