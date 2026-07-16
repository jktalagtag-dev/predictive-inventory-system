import { useQuery } from '@tanstack/react-query'
import { categoryOptionQueryKeys, getCategoryOptions, getProducts, getUnitOptions, productQueryKeys, unitOptionQueryKeys } from '@/features/products/api/productsApi'
import type { ProductFilters } from '@/features/products/types/product'

export function useProducts(filters: ProductFilters) {
  return useQuery({ queryKey: productQueryKeys.list(filters), queryFn: () => getProducts(filters) })
}

export function useCategoryOptions() {
  return useQuery({ queryKey: categoryOptionQueryKeys.list(), queryFn: getCategoryOptions, staleTime: 5 * 60_000 })
}

export function useUnitOptions() {
  return useQuery({ queryKey: unitOptionQueryKeys.list(), queryFn: getUnitOptions, staleTime: 5 * 60_000 })
}
