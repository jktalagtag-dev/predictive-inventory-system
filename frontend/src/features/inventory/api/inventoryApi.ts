import { apiClient } from '@/shared/api/client'
import type {
  AdjustmentFormValues,
  InventoryAdjustment,
  InventoryAdjustmentFilters,
  InventoryBalance,
  InventoryBalanceFilters,
  InventoryMovement,
  InventoryMovementFilters,
  Paginated,
} from '@/features/inventory/types/inventory'

type ApiEnvelope<T> = { data: T; meta?: Paginated<unknown>['meta'] }

export const inventoryQueryKeys = {
  balances: (filters: InventoryBalanceFilters) => ['inventory-balances', filters] as const,
  movements: (filters: InventoryMovementFilters) => ['inventory-movements', filters] as const,
  adjustmentLists: () => ['inventory-adjustments'] as const,
  adjustmentList: (filters: InventoryAdjustmentFilters) => ['inventory-adjustments', filters] as const,
  adjustmentDetail: (id: string) => ['inventory-adjustments', 'detail', id] as const,
}

export async function getInventoryBalances(filters: InventoryBalanceFilters): Promise<Paginated<InventoryBalance>> {
  const response = await apiClient.get<ApiEnvelope<InventoryBalance[]>>('/inventory/balances', {
    params: {
      branchId: filters.branchId ?? undefined,
      availability: filters.availability === 'all' ? undefined : filters.availability,
      search: filters.search || undefined,
      page: filters.page,
      perPage: filters.perPage,
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function getInventoryMovements(filters: InventoryMovementFilters): Promise<Paginated<InventoryMovement>> {
  const response = await apiClient.get<ApiEnvelope<InventoryMovement[]>>('/inventory/movements', {
    params: {
      branchId: filters.branchId ?? undefined,
      movementType: filters.movementType === 'all' ? undefined : filters.movementType,
      page: filters.page,
      perPage: filters.perPage,
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function getInventoryAdjustments(filters: InventoryAdjustmentFilters): Promise<Paginated<InventoryAdjustment>> {
  const response = await apiClient.get<ApiEnvelope<InventoryAdjustment[]>>('/inventory/adjustments', {
    params: {
      branchId: filters.branchId ?? undefined,
      status: filters.status === 'all' ? undefined : filters.status,
      page: filters.page,
      perPage: filters.perPage,
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function getInventoryAdjustment(id: string): Promise<InventoryAdjustment> {
  const response = await apiClient.get<ApiEnvelope<InventoryAdjustment>>(`/inventory/adjustments/${id}`)
  return response.data.data
}

export async function createInventoryAdjustment(branchId: string, values: AdjustmentFormValues): Promise<InventoryAdjustment> {
  const response = await apiClient.post<ApiEnvelope<InventoryAdjustment>>('/inventory/adjustments', {
    branchId,
    reasonCode: values.reasonCode,
    reasonNote: values.reasonNote || undefined,
    effectiveAt: values.effectiveAt,
    lines: values.lines.map((line) => ({
      productId: line.productId,
      quantityDelta: line.quantityDelta,
      unitCost: line.unitCost || undefined,
      notes: line.notes || undefined,
    })),
  })
  return response.data.data
}

export async function approveInventoryAdjustment(adjustment: InventoryAdjustment): Promise<InventoryAdjustment> {
  const response = await apiClient.post<ApiEnvelope<InventoryAdjustment>>(`/inventory/adjustments/${adjustment.id}/approve`, { version: adjustment.version })
  return response.data.data
}

export async function postInventoryAdjustment(adjustment: InventoryAdjustment): Promise<InventoryAdjustment> {
  const response = await apiClient.post<ApiEnvelope<InventoryAdjustment>>(
    `/inventory/adjustments/${adjustment.id}/post`,
    { version: adjustment.version },
    { headers: { 'Idempotency-Key': crypto.randomUUID() } },
  )
  return response.data.data
}

export async function reverseInventoryAdjustment(adjustment: InventoryAdjustment, reason: string): Promise<InventoryAdjustment> {
  const response = await apiClient.post<ApiEnvelope<InventoryAdjustment>>(
    `/inventory/adjustments/${adjustment.id}/reverse`,
    { reason, version: adjustment.version },
    { headers: { 'Idempotency-Key': crypto.randomUUID() } },
  )
  return response.data.data
}
