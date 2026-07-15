export type Category = {
  id: string
  parentCategoryId: string | null
  parentName: string | null
  code: string
  name: string
  description: string | null
  isActive: boolean
  productCount: number
  updatedAt: string
  version: number
}

export type CategoryFilters = {
  search: string
  parent: 'all' | 'top_level' | string
  active: 'all' | 'active' | 'inactive'
  page: number
  perPage: number
}

export type CategoryFormValues = {
  parentCategoryId: string
  code: string
  name: string
  description: string
  isActive: boolean
}

export type PaginatedCategories = {
  data: Category[]
  meta: { page: number; perPage: number; total: number }
}
