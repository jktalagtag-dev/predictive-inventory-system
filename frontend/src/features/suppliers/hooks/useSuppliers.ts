import { useQuery } from '@tanstack/react-query'
import { getSupplierOptions, getSuppliers, supplierOptionQueryKeys, supplierQueryKeys } from '@/features/suppliers/api/suppliersApi'
import type { SupplierFilters } from '@/features/suppliers/types/supplier'

export function useSuppliers(filters: SupplierFilters) {
  return useQuery({ queryKey: supplierQueryKeys.list(filters), queryFn: () => getSuppliers(filters) })
}

export function useSupplierOptions() {
  return useQuery({ queryKey: supplierOptionQueryKeys.list(), queryFn: getSupplierOptions, staleTime: 5 * 60_000 })
}
