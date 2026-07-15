import { useQuery } from '@tanstack/react-query'
import { getCategories, getCategoriesPlaceholder, categoryQueryKeys } from '@/features/categories/api/categoriesApi'
import type { CategoryFilters } from '@/features/categories/types/category'

export function useCategories(filters: CategoryFilters) {
  return useQuery({ queryKey: categoryQueryKeys.list(filters), queryFn: () => getCategories(filters), placeholderData: () => getCategoriesPlaceholder(filters), retry: false })
}
