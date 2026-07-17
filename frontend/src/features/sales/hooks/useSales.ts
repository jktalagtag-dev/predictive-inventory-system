import { useQuery } from '@tanstack/react-query'
import { getSale, getSales, saleQueryKeys } from '@/features/sales/api/salesApi'
import type { SaleFilters } from '@/features/sales/types/sale'

export function useSales(filters: SaleFilters) {
  return useQuery({ queryKey: saleQueryKeys.list(filters), queryFn: () => getSales(filters), enabled: filters.branchId !== null })
}

export function useSale(id: string | undefined) {
  return useQuery({ queryKey: saleQueryKeys.detail(id ?? ''), queryFn: () => getSale(id as string), enabled: id !== undefined })
}
