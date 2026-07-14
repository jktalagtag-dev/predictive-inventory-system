export type StockStatus = 'in_stock' | 'low_stock' | 'critical_stock' | 'out_of_stock'

export type Product = {
  id: string
  sku: string
  barcode: string | null
  name: string
  category: string
  stockUnit: string
  productType: 'stock' | 'non_stock' | 'service'
  taxRate: string
  isActive: boolean
  stock: {
    onHand: string
    available: string
    incoming: string
    reorderPoint: string
    status: StockStatus
  }
  updatedAt: string
  version: number
}

export type ProductFilters = {
  search: string
  category: string
  stockStatus: StockStatus | 'all'
  active: 'all' | 'active' | 'inactive'
  page: number
  perPage: number
}

export type ProductFormValues = {
  sku: string
  barcode: string
  name: string
  category: string
  stockUnit: string
  productType: Product['productType']
  taxRate: string
  isActive: boolean
}

export type PaginatedProducts = {
  data: Product[]
  meta: { page: number; perPage: number; total: number }
}
