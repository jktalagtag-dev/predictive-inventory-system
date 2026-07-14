import { apiClient } from '@/shared/api/client'
import type { PaginatedProducts, Product, ProductFilters, ProductFormValues } from '@/features/products/types/product'

type ApiEnvelope<T> = { data: T; meta?: PaginatedProducts['meta'] }

export const productQueryKeys = {
  lists: () => ['products'] as const,
  list: (filters: ProductFilters) => ['products', filters] as const,
}

const previewProducts: Product[] = [
  { id: 'prd-001', sku: 'SHX-FLT-010', barcode: '4801234567891', name: '10 in. Sediment Filter Cartridge', category: 'Filter Cartridges', stockUnit: 'EA', productType: 'stock', taxRate: '12.0000', isActive: true, stock: { onHand: '8', available: '6', incoming: '0', reorderPoint: '12', status: 'critical_stock' }, updatedAt: '2026-07-14T03:52:00.000Z', version: 1 },
  { id: 'prd-002', sku: 'SHX-RO-4040', barcode: null, name: 'Reverse Osmosis Membrane 4040', category: 'Membranes', stockUnit: 'EA', productType: 'stock', taxRate: '12.0000', isActive: true, stock: { onHand: '5', available: '4', incoming: '12', reorderPoint: '8', status: 'critical_stock' }, updatedAt: '2026-07-14T02:44:00.000Z', version: 2 },
  { id: 'prd-003', sku: 'SHX-CAR-025', barcode: '4801234567907', name: 'Activated Carbon Media 25 kg', category: 'Filter Media', stockUnit: 'BAG', productType: 'stock', taxRate: '12.0000', isActive: true, stock: { onHand: '24', available: '18', incoming: '40', reorderPoint: '24', status: 'low_stock' }, updatedAt: '2026-07-13T08:32:00.000Z', version: 1 },
  { id: 'prd-004', sku: 'SHX-GAU-160', barcode: null, name: 'Pressure Gauge 0–160 PSI', category: 'Instrumentation', stockUnit: 'EA', productType: 'stock', taxRate: '12.0000', isActive: true, stock: { onHand: '16', available: '9', incoming: '0', reorderPoint: '10', status: 'low_stock' }, updatedAt: '2026-07-13T05:18:00.000Z', version: 1 },
  { id: 'prd-005', sku: 'SHX-VAL-1IN', barcode: '4801234567914', name: 'PVC Ball Valve 1 in.', category: 'Valves and Fittings', stockUnit: 'EA', productType: 'stock', taxRate: '12.0000', isActive: true, stock: { onHand: '96', available: '96', incoming: '0', reorderPoint: '30', status: 'in_stock' }, updatedAt: '2026-07-12T07:10:00.000Z', version: 1 },
  { id: 'prd-006', sku: 'SHX-SVC-INS', barcode: null, name: 'Installation Service', category: 'Services', stockUnit: 'EA', productType: 'service', taxRate: '12.0000', isActive: true, stock: { onHand: '0', available: '0', incoming: '0', reorderPoint: '0', status: 'in_stock' }, updatedAt: '2026-07-11T03:10:00.000Z', version: 1 },
]

function filterPreviewProducts(filters: ProductFilters): PaginatedProducts {
  const search = filters.search.trim().toLowerCase()
  const filtered = previewProducts.filter((product) => {
    const matchesSearch = !search || [product.name, product.sku, product.barcode ?? ''].some((value) => value.toLowerCase().includes(search))
    const matchesCategory = filters.category === 'all' || product.category === filters.category
    const matchesStatus = filters.stockStatus === 'all' || product.stock.status === filters.stockStatus
    const matchesActive = filters.active === 'all' || product.isActive === (filters.active === 'active')
    return matchesSearch && matchesCategory && matchesStatus && matchesActive
  })
  const start = (filters.page - 1) * filters.perPage
  return { data: filtered.slice(start, start + filters.perPage), meta: { page: filters.page, perPage: filters.perPage, total: filtered.length } }
}

export function getProductsPlaceholder(filters: ProductFilters): PaginatedProducts {
  return filterPreviewProducts(filters)
}

export async function getProducts(filters: ProductFilters): Promise<PaginatedProducts> {
  const response = await apiClient.get<ApiEnvelope<Product[]>>('/products', { params: { search: filters.search || undefined, category: filters.category === 'all' ? undefined : filters.category, stockStatus: filters.stockStatus === 'all' ? undefined : filters.stockStatus, isActive: filters.active === 'all' ? undefined : filters.active === 'active', page: filters.page, perPage: filters.perPage, sort: 'name' } })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function createProduct(values: ProductFormValues): Promise<Product> {
  const response = await apiClient.post<ApiEnvelope<Product>>('/products', values)
  return response.data.data
}

export async function updateProduct(product: Product, values: ProductFormValues): Promise<Product> {
  const response = await apiClient.patch<ApiEnvelope<Product>>(`/products/${product.id}`, { ...values, version: product.version })
  return response.data.data
}
