import { apiClient } from '@/shared/api/client'
import type {
  CreateReorderPolicyPayload,
  EoqCalculation,
  PaginatedReorderPolicies,
  PaginatedRestockingAlerts,
  ReorderPolicy,
  ReorderPolicyFilters,
  RestockingAlert,
  RestockingAlertFilters,
} from '@/features/restocking/types/restocking'

type ApiEnvelope<T> = { data: T; meta?: { page: number; perPage: number; total: number } }

export const reorderPolicyQueryKeys = {
  lists: () => ['reorder-policies'] as const,
  list: (filters: ReorderPolicyFilters) => ['reorder-policies', filters] as const,
  detail: (id: string) => ['reorder-policies', 'detail', id] as const,
  eoqHistory: (id: string) => ['reorder-policies', id, 'eoq'] as const,
}

export const restockingAlertQueryKeys = {
  lists: () => ['restocking-alerts'] as const,
  list: (filters: RestockingAlertFilters) => ['restocking-alerts', filters] as const,
  detail: (id: string) => ['restocking-alerts', 'detail', id] as const,
}

export async function getReorderPolicies(filters: ReorderPolicyFilters): Promise<PaginatedReorderPolicies> {
  const response = await apiClient.get<ApiEnvelope<ReorderPolicy[]>>('/reorder-policies', {
    params: { branchId: filters.branchId ?? undefined, page: filters.page, perPage: filters.perPage },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function createReorderPolicy(payload: CreateReorderPolicyPayload): Promise<ReorderPolicy> {
  const response = await apiClient.post<ApiEnvelope<ReorderPolicy>>('/reorder-policies', payload)
  return response.data.data
}

export async function recalculateRop(policy: ReorderPolicy, forecastRunId?: string): Promise<ReorderPolicy> {
  const response = await apiClient.post<ApiEnvelope<ReorderPolicy>>(`/reorder-policies/${policy.id}/recalculate-rop`, { forecastRunId })
  return response.data.data
}

export async function calculateEoq(policy: ReorderPolicy, annualDemandQuantity: string, orderingCost: string, annualHoldingCostPerUnit: string, currencyCode: string): Promise<EoqCalculation> {
  const response = await apiClient.post<ApiEnvelope<EoqCalculation>>(`/reorder-policies/${policy.id}/eoq-calculations`, {
    annualDemandQuantity, orderingCost, annualHoldingCostPerUnit, currencyCode,
  })
  return response.data.data
}

export async function getEoqHistory(policyId: string): Promise<EoqCalculation[]> {
  const response = await apiClient.get<ApiEnvelope<EoqCalculation[]>>(`/reorder-policies/${policyId}/eoq-calculations`)
  return response.data.data
}

export async function getRestockingAlerts(filters: RestockingAlertFilters): Promise<PaginatedRestockingAlerts> {
  const response = await apiClient.get<ApiEnvelope<RestockingAlert[]>>('/restocking-alerts', {
    params: {
      branchId: filters.branchId ?? undefined,
      status: filters.status === 'all' ? undefined : filters.status,
      severity: filters.severity === 'all' ? undefined : filters.severity,
      page: filters.page,
      perPage: filters.perPage,
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function getRestockingAlert(id: string): Promise<RestockingAlert> {
  const response = await apiClient.get<ApiEnvelope<RestockingAlert>>(`/restocking-alerts/${id}`)
  return response.data.data
}

export async function evaluateRestockingAlerts(branchId: string): Promise<number> {
  const response = await apiClient.post<{ data: { evaluatedActiveAlertCount: number } }>('/restocking-alerts/evaluate', { branchId })
  return response.data.data.evaluatedActiveAlertCount
}

export async function acknowledgeAlert(alert: RestockingAlert, note?: string): Promise<RestockingAlert> {
  const response = await apiClient.post<ApiEnvelope<RestockingAlert>>(`/restocking-alerts/${alert.id}/acknowledge`, { note, version: alert.version })
  return response.data.data
}

export async function resolveAlert(alert: RestockingAlert, reason: string): Promise<RestockingAlert> {
  const response = await apiClient.post<ApiEnvelope<RestockingAlert>>(`/restocking-alerts/${alert.id}/resolve`, { reason, version: alert.version })
  return response.data.data
}

export async function dismissAlert(alert: RestockingAlert, reason: string): Promise<RestockingAlert> {
  const response = await apiClient.post<ApiEnvelope<RestockingAlert>>(`/restocking-alerts/${alert.id}/dismiss`, { reason, version: alert.version })
  return response.data.data
}
