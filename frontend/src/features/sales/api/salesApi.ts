import { apiClient } from '@/shared/api/client'
import type { PaginatedSales, RefundLineInput, RefundPaymentInput, Sale, SaleFilters } from '@/features/sales/types/sale'

type ApiEnvelope<T> = { data: T; meta?: PaginatedSales['meta'] }

export const saleQueryKeys = {
  lists: () => ['sales'] as const,
  list: (filters: SaleFilters) => ['sales', filters] as const,
  detail: (id: string) => ['sales', 'detail', id] as const,
}

export async function getSales(filters: SaleFilters): Promise<PaginatedSales> {
  const response = await apiClient.get<ApiEnvelope<Sale[]>>('/sales', {
    params: {
      branchId: filters.branchId ?? undefined,
      status: filters.status === 'all' ? undefined : filters.status,
      saleNumber: filters.saleNumber || undefined,
      page: filters.page,
      perPage: filters.perPage,
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function getSale(id: string): Promise<Sale> {
  const response = await apiClient.get<ApiEnvelope<Sale>>(`/sales/${id}`)
  return response.data.data
}

export async function voidSale(sale: Sale, reason: string): Promise<Sale> {
  const response = await apiClient.post<ApiEnvelope<Sale>>(
    `/sales/${sale.id}/void`,
    { reason, version: sale.version },
    { headers: { 'Idempotency-Key': crypto.randomUUID() } },
  )
  return response.data.data
}

export async function refundSale(sale: Sale, reason: string, lines: RefundLineInput[], payments: RefundPaymentInput[]): Promise<Sale> {
  const response = await apiClient.post<ApiEnvelope<Sale>>(
    `/sales/${sale.id}/refunds`,
    { reason, version: sale.version, lines, payments },
    { headers: { 'Idempotency-Key': crypto.randomUUID() } },
  )
  return response.data.data
}
