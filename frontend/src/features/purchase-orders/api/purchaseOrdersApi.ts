import { apiClient } from '@/shared/api/client'
import type { PaginatedPurchaseOrders, PurchaseOrder, PurchaseOrderFilters, PurchaseOrderFormValues } from '@/features/purchase-orders/types/purchaseOrder'

type ApiEnvelope<T> = { data: T; meta?: PaginatedPurchaseOrders['meta'] }

export const purchaseOrderQueryKeys = {
  lists: () => ['purchase-orders'] as const,
  list: (filters: PurchaseOrderFilters) => ['purchase-orders', filters] as const,
  detail: (id: string) => ['purchase-orders', 'detail', id] as const,
}

export async function getPurchaseOrders(filters: PurchaseOrderFilters): Promise<PaginatedPurchaseOrders> {
  const response = await apiClient.get<ApiEnvelope<PurchaseOrder[]>>('/purchase-orders', {
    params: {
      branchId: filters.branchId ?? undefined,
      supplierId: filters.supplierId === 'all' ? undefined : filters.supplierId,
      status: filters.status === 'all' ? undefined : filters.status,
      search: filters.search || undefined,
      page: filters.page,
      perPage: filters.perPage,
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function getPurchaseOrder(id: string): Promise<PurchaseOrder> {
  const response = await apiClient.get<ApiEnvelope<PurchaseOrder>>(`/purchase-orders/${id}`)
  return response.data.data
}

export async function createPurchaseOrder(branchId: string, values: PurchaseOrderFormValues): Promise<PurchaseOrder> {
  const response = await apiClient.post<ApiEnvelope<PurchaseOrder>>('/purchase-orders', {
    branchId,
    supplierId: values.supplierId,
    currencyCode: values.currencyCode,
    expectedReceiptAt: values.expectedReceiptAt || undefined,
    supplierReference: values.supplierReference || undefined,
    notes: values.notes || undefined,
    lines: values.lines.map((line) => ({
      productId: line.productId,
      unitId: line.unitId,
      orderedQuantity: line.orderedQuantity,
      unitCost: line.unitCost,
      taxRate: line.taxRate || undefined,
      discountAmount: line.discountAmount || undefined,
    })),
  })
  return response.data.data
}

export async function submitPurchaseOrder(po: PurchaseOrder): Promise<PurchaseOrder> {
  const response = await apiClient.post<ApiEnvelope<PurchaseOrder>>(`/purchase-orders/${po.id}/submit`, { version: po.version })
  return response.data.data
}

export async function decidePurchaseOrder(po: PurchaseOrder, decision: 'approved' | 'rejected', reason?: string): Promise<PurchaseOrder> {
  const response = await apiClient.post<ApiEnvelope<PurchaseOrder>>(`/purchase-orders/${po.id}/approvals`, { decision, reason, version: po.version })
  return response.data.data
}

export async function markPurchaseOrderOrdered(po: PurchaseOrder, orderedAt: string, supplierReference?: string): Promise<PurchaseOrder> {
  const response = await apiClient.post<ApiEnvelope<PurchaseOrder>>(`/purchase-orders/${po.id}/mark-ordered`, { orderedAt, supplierReference, version: po.version })
  return response.data.data
}

export async function cancelPurchaseOrder(po: PurchaseOrder, reason: string): Promise<PurchaseOrder> {
  const response = await apiClient.post<ApiEnvelope<PurchaseOrder>>(`/purchase-orders/${po.id}/cancel`, { reason, version: po.version })
  return response.data.data
}

export async function closePurchaseOrder(po: PurchaseOrder, reason?: string): Promise<PurchaseOrder> {
  const response = await apiClient.post<ApiEnvelope<PurchaseOrder>>(`/purchase-orders/${po.id}/close`, { reason, version: po.version })
  return response.data.data
}
