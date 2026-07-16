import { useQuery } from '@tanstack/react-query'
import { getPurchaseOrder, getPurchaseOrders, purchaseOrderQueryKeys } from '@/features/purchase-orders/api/purchaseOrdersApi'
import type { PurchaseOrderFilters } from '@/features/purchase-orders/types/purchaseOrder'

export function usePurchaseOrders(filters: PurchaseOrderFilters) {
  return useQuery({ queryKey: purchaseOrderQueryKeys.list(filters), queryFn: () => getPurchaseOrders(filters), enabled: filters.branchId !== null })
}

export function usePurchaseOrder(id: string | undefined) {
  return useQuery({ queryKey: purchaseOrderQueryKeys.detail(id ?? ''), queryFn: () => getPurchaseOrder(id as string), enabled: id !== undefined })
}
