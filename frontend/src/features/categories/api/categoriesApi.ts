import { apiClient } from '@/shared/api/client'
import type { Category, CategoryFilters, CategoryFormValues, PaginatedCategories } from '@/features/categories/types/category'

type ApiEnvelope<T> = { data: T; meta?: PaginatedCategories['meta'] }

export const categoryQueryKeys = {
  lists: () => ['categories'] as const,
  list: (filters: CategoryFilters) => ['categories', filters] as const,
}

const previewCategories: Category[] = [
  { id: 'cat-001', parentCategoryId: null, parentName: null, code: 'FILT', name: 'Filter Cartridges', description: 'Sediment, carbon block, and specialty filter cartridges.', isActive: true, productCount: 18, updatedAt: '2026-07-10T02:00:00.000Z', version: 1 },
  { id: 'cat-002', parentCategoryId: null, parentName: null, code: 'MEMB', name: 'Membranes', description: 'Reverse osmosis and ultrafiltration membranes.', isActive: true, productCount: 6, updatedAt: '2026-07-09T05:30:00.000Z', version: 1 },
  { id: 'cat-003', parentCategoryId: 'cat-001', parentName: 'Filter Cartridges', code: 'FILT-MEDIA', name: 'Filter Media', description: 'Loose and bagged filtration media.', isActive: true, productCount: 4, updatedAt: '2026-07-08T09:12:00.000Z', version: 2 },
  { id: 'cat-004', parentCategoryId: null, parentName: null, code: 'INST', name: 'Instrumentation', description: 'Gauges, meters, and monitoring instruments.', isActive: true, productCount: 9, updatedAt: '2026-07-07T01:45:00.000Z', version: 1 },
  { id: 'cat-005', parentCategoryId: null, parentName: null, code: 'VALV', name: 'Valves and Fittings', description: 'Ball valves, unions, and pipe fittings.', isActive: true, productCount: 27, updatedAt: '2026-07-05T06:20:00.000Z', version: 1 },
  { id: 'cat-006', parentCategoryId: null, parentName: null, code: 'SVC', name: 'Services', description: 'Installation and maintenance service items.', isActive: false, productCount: 1, updatedAt: '2026-06-20T03:10:00.000Z', version: 3 },
]

function filterPreviewCategories(filters: CategoryFilters): PaginatedCategories {
  const search = filters.search.trim().toLowerCase()
  const filtered = previewCategories.filter((category) => {
    const matchesSearch = !search || [category.name, category.code].some((value) => value.toLowerCase().includes(search))
    const matchesParent = filters.parent === 'all' || (filters.parent === 'top_level' ? category.parentCategoryId === null : category.parentCategoryId === filters.parent)
    const matchesActive = filters.active === 'all' || category.isActive === (filters.active === 'active')
    return matchesSearch && matchesParent && matchesActive
  })
  const start = (filters.page - 1) * filters.perPage
  return { data: filtered.slice(start, start + filters.perPage), meta: { page: filters.page, perPage: filters.perPage, total: filtered.length } }
}

export function getCategoriesPlaceholder(filters: CategoryFilters): PaginatedCategories {
  return filterPreviewCategories(filters)
}

export async function getCategories(filters: CategoryFilters): Promise<PaginatedCategories> {
  const response = await apiClient.get<ApiEnvelope<Category[]>>('/categories', { params: { search: filters.search || undefined, parentCategoryId: filters.parent === 'all' || filters.parent === 'top_level' ? undefined : filters.parent, topLevelOnly: filters.parent === 'top_level' ? true : undefined, isActive: filters.active === 'all' ? undefined : filters.active === 'active', page: filters.page, perPage: filters.perPage, sort: 'name' } })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function createCategory(values: CategoryFormValues): Promise<Category> {
  const response = await apiClient.post<ApiEnvelope<Category>>('/categories', { ...values, parentCategoryId: values.parentCategoryId || null })
  return response.data.data
}

export async function updateCategory(category: Category, values: CategoryFormValues): Promise<Category> {
  const response = await apiClient.patch<ApiEnvelope<Category>>(`/categories/${category.id}`, { ...values, parentCategoryId: values.parentCategoryId || null, version: category.version })
  return response.data.data
}

export async function archiveCategory(category: Category): Promise<void> {
  await apiClient.delete(`/categories/${category.id}`, { params: { version: category.version } })
}
