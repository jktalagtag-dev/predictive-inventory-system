export type ProductType = 'stock' | 'non_stock' | 'service'

export type CategoryOption = {
  id: string
  name: string
}

export type UnitOption = {
  id: string
  code: string
  name: string
  symbol: string
}

export type ProductStock = {
  onHandQuantity: string
  availableQuantity: string
  incomingQuantity: string
  lastMovementAt: string | null
}

export type Product = {
  id: string
  sku: string
  barcode: string | null
  name: string
  description: string | null
  category: CategoryOption | null
  stockUnit: { id: string; code: string; symbol: string } | null
  productType: ProductType
  isActive: boolean
  isLotTracked: boolean
  isSerialTracked: boolean
  isExpiryTracked: boolean
  defaultTaxRate: string
  sellingPrice: string
  // Present only when the list/detail request included a branchId the
  // caller is authorized to see; null means "not requested or not
  // authorized", never a fabricated zero.
  stock: ProductStock | null
  updatedAt: string
  version: number
}

export type ProductFilters = {
  search: string
  categoryId: string | 'all'
  productType: ProductType | 'all'
  active: 'all' | 'active' | 'inactive'
  branchId: string | null
  page: number
  perPage: number
}

export type ProductFormValues = {
  categoryId: string
  stockUnitId: string
  sku: string
  barcode: string
  name: string
  description: string
  productType: ProductType
  defaultTaxRate: string
  sellingPrice: string
  isActive: boolean
  isLotTracked: boolean
  isSerialTracked: boolean
  isExpiryTracked: boolean
}

export type PaginatedProducts = {
  data: Product[]
  meta: { page: number; perPage: number; total: number }
}
