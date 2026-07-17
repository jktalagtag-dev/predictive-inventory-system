import { apiClient } from '@/shared/api/client'
import type { GoodsReceipt, GoodsReceiptFilters, GoodsReceiptFormValues, PaginatedGoodsReceipts } from '@/features/receiving/types/goodsReceipt'

type ApiEnvelope<T> = { data: T; meta?: PaginatedGoodsReceipts['meta'] }

export const goodsReceiptQueryKeys = {
  lists: () => ['goods-receipts'] as const,
  list: (filters: GoodsReceiptFilters) => ['goods-receipts', filters] as const,
  detail: (id: string) => ['goods-receipts', 'detail', id] as const,
}

export async function getGoodsReceipts(filters: GoodsReceiptFilters): Promise<PaginatedGoodsReceipts> {
  const response = await apiClient.get<ApiEnvelope<GoodsReceipt[]>>('/goods-receipts', {
    params: {
      branchId: filters.branchId ?? undefined,
      purchaseOrderId: filters.purchaseOrderId === 'all' ? undefined : filters.purchaseOrderId,
      status: filters.status === 'all' ? undefined : filters.status,
      page: filters.page,
      perPage: filters.perPage,
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function getGoodsReceipt(id: string): Promise<GoodsReceipt> {
  const response = await apiClient.get<ApiEnvelope<GoodsReceipt>>(`/goods-receipts/${id}`)
  return response.data.data
}

export async function createGoodsReceipt(branchId: string, values: GoodsReceiptFormValues): Promise<GoodsReceipt> {
  const response = await apiClient.post<ApiEnvelope<GoodsReceipt>>('/goods-receipts', {
    branchId,
    purchaseOrderId: values.purchaseOrderId,
    supplierDeliveryNumber: values.supplierDeliveryNumber || undefined,
    receivedAt: values.receivedAt,
    notes: values.notes || undefined,
    lines: values.lines.map((line) => ({
      purchaseOrderLineId: line.purchaseOrderLineId,
      receivedQuantity: line.receivedQuantity,
      acceptedQuantity: line.acceptedQuantity,
      rejectedQuantity: line.rejectedQuantity,
      lotNumber: line.lotNumber || undefined,
      serialNumber: line.serialNumber || undefined,
      expiryDate: line.expiryDate || undefined,
      rejectionReason: line.rejectionReason || undefined,
      notes: line.notes || undefined,
    })),
  })
  return response.data.data
}

export async function postGoodsReceipt(receipt: GoodsReceipt): Promise<GoodsReceipt> {
  const response = await apiClient.post<ApiEnvelope<GoodsReceipt>>(
    `/goods-receipts/${receipt.id}/post`,
    { version: receipt.version },
    { headers: { 'Idempotency-Key': crypto.randomUUID() } },
  )
  return response.data.data
}

export async function reverseGoodsReceipt(receipt: GoodsReceipt, reason: string): Promise<GoodsReceipt> {
  const response = await apiClient.post<ApiEnvelope<GoodsReceipt>>(
    `/goods-receipts/${receipt.id}/reverse`,
    { reason, version: receipt.version },
    { headers: { 'Idempotency-Key': crypto.randomUUID() } },
  )
  return response.data.data
}
