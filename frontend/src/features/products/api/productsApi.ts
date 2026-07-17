import { apiClient } from '@/shared/api/client'
import type { CategoryOption, PaginatedProducts, Product, ProductFilters, ProductFormValues, UnitOption } from '@/features/products/types/product'

type ApiEnvelope<T> = { data: T; meta?: PaginatedProducts['meta'] }

export const productQueryKeys = {
  lists: () => ['products'] as const,
  list: (filters: ProductFilters) => ['products', filters] as const,
}

export const categoryOptionQueryKeys = { list: () => ['categories', 'options'] as const }
export const unitOptionQueryKeys = { list: () => ['units-of-measure', 'options'] as const }

export async function getProducts(filters: ProductFilters): Promise<PaginatedProducts> {
  const response = await apiClient.get<ApiEnvelope<Product[]>>('/products', {
    params: {
      search: filters.search || undefined,
      categoryId: filters.categoryId === 'all' ? undefined : filters.categoryId,
      productType: filters.productType === 'all' ? undefined : filters.productType,
      isActive: filters.active === 'all' ? undefined : filters.active === 'active',
      branchId: filters.branchId ?? undefined,
      page: filters.page,
      perPage: filters.perPage,
      sort: 'name',
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function createProduct(values: ProductFormValues): Promise<Product> {
  const response = await apiClient.post<ApiEnvelope<Product>>('/products', {
    categoryId: values.categoryId,
    stockUnitId: values.stockUnitId,
    sku: values.sku,
    barcode: values.barcode || undefined,
    name: values.name,
    description: values.description || undefined,
    productType: values.productType,
    defaultTaxRate: values.defaultTaxRate,
    sellingPrice: values.sellingPrice,
    isActive: values.isActive,
    isLotTracked: values.isLotTracked,
    isSerialTracked: values.isSerialTracked,
    isExpiryTracked: values.isExpiryTracked,
  })
  return response.data.data
}

export async function updateProduct(product: Product, values: ProductFormValues): Promise<Product> {
  const response = await apiClient.patch<ApiEnvelope<Product>>(`/products/${product.id}`, {
    categoryId: values.categoryId,
    stockUnitId: values.stockUnitId,
    sku: values.sku,
    barcode: values.barcode || null,
    name: values.name,
    description: values.description || null,
    productType: values.productType,
    defaultTaxRate: values.defaultTaxRate,
    sellingPrice: values.sellingPrice,
    isActive: values.isActive,
    isLotTracked: values.isLotTracked,
    isSerialTracked: values.isSerialTracked,
    isExpiryTracked: values.isExpiryTracked,
    version: product.version,
  })
  return response.data.data
}

export async function archiveProduct(product: Product): Promise<void> {
  await apiClient.delete(`/products/${product.id}`, { params: { version: product.version } })
}

export async function getCategoryOptions(): Promise<CategoryOption[]> {
  const response = await apiClient.get<ApiEnvelope<Array<{ id: string; name: string }>>>('/categories', { params: { isActive: true, perPage: 100 } })
  return response.data.data.map((category) => ({ id: category.id, name: category.name }))
}

export async function getUnitOptions(): Promise<UnitOption[]> {
  const response = await apiClient.get<ApiEnvelope<Array<{ id: string; code: string; name: string; symbol: string }>>>('/units-of-measure', { params: { isActive: true } })
  return response.data.data.map((unit) => ({ id: unit.id, code: unit.code, name: unit.name, symbol: unit.symbol }))
}

export async function getProductOptions(): Promise<Array<{ id: string; sku: string; name: string }>> {
  const response = await apiClient.get<ApiEnvelope<Product[]>>('/products', { params: { isActive: true, perPage: 100, sort: 'name' } })
  return response.data.data.map((product) => ({ id: product.id, sku: product.sku, name: product.name }))
}
