import { apiClient } from '@/shared/api/client'
import type { FinalizeSalePayload, PosProduct } from '@/features/pos/types/pos'
import type { Sale } from '@/features/sales/types/sale'

type ApiEnvelope<T> = { data: T; meta?: { page: number; perPage: number; total: number } }

export const posProductQueryKeys = {
  list: (branchId: string | null, query: string) => ['pos-products', branchId, query] as const,
}

export async function getPosProducts(branchId: string, query: string): Promise<PosProduct[]> {
  const response = await apiClient.get<ApiEnvelope<PosProduct[]>>('/pos/products', {
    params: { branchId, query: query || undefined, perPage: 25 },
  })
  return response.data.data
}

export async function finalizeSale(payload: FinalizeSalePayload): Promise<Sale> {
  const response = await apiClient.post<ApiEnvelope<Sale>>(
    '/sales',
    {
      branchId: payload.branchId,
      soldAt: payload.soldAt,
      currencyCode: payload.currencyCode,
      notes: payload.notes || undefined,
      approvedByUserId: payload.approvedByUserId || undefined,
      lines: payload.lines,
      payments: payload.payments,
    },
    { headers: { 'Idempotency-Key': crypto.randomUUID() } },
  )
  return response.data.data
}
