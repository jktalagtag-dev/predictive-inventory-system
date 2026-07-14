import { useQuery } from '@tanstack/react-query'
import { getProducts, getProductsPlaceholder, productQueryKeys } from '@/features/products/api/productsApi'
import type { ProductFilters } from '@/features/products/types/product'

export function useProducts(filters: ProductFilters) {
  return useQuery({ queryKey: productQueryKeys.list(filters), queryFn: () => getProducts(filters), placeholderData: () => getProductsPlaceholder(filters), retry: false })
}
