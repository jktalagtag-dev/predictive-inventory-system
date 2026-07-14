import { type ChangeEvent, useState } from 'react'
import { PackagePlus, Search } from 'lucide-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { createProduct, productQueryKeys, updateProduct } from '@/features/products/api/productsApi'
import { ProductDetailsDrawer } from '@/features/products/components/ProductDetailsDrawer'
import { ProductFormDialog } from '@/features/products/components/ProductFormDialog'
import { ProductTable } from '@/features/products/components/ProductTable'
import { useProducts } from '@/features/products/hooks/useProducts'
import type { Product, ProductFilters, ProductFormValues, StockStatus } from '@/features/products/types/product'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

const defaultFilters: ProductFilters = { search: '', category: 'all', stockStatus: 'all', active: 'all', page: 1, perPage: 10 }

export default function ProductsPage() {
  const [filters, setFilters] = useState<ProductFilters>(defaultFilters)
  const [selectedProduct, setSelectedProduct] = useState<Product | undefined>()
  const [editingProduct, setEditingProduct] = useState<Product | undefined>()
  const [isFormOpen, setIsFormOpen] = useState(false)
  const queryClient = useQueryClient()
  const productsQuery = useProducts(filters)
  const invalidate = () => queryClient.invalidateQueries({ queryKey: productQueryKeys.lists() })
  const createMutation = useMutation({ mutationFn: createProduct, onSuccess: () => { void invalidate(); setIsFormOpen(false) } })
  const updateMutation = useMutation({ mutationFn: ({ product, values }: { product: Product; values: ProductFormValues }) => updateProduct(product, values), onSuccess: () => { void invalidate(); setIsFormOpen(false); setEditingProduct(undefined) } })
  const totalPages = Math.max(1, Math.ceil((productsQuery.data?.meta.total ?? 0) / filters.perPage))
  const error = (createMutation.error ?? updateMutation.error) as ApiError | null
  const products = productsQuery.data?.data ?? []
  const categories = ['all', 'Filter Cartridges', 'Membranes', 'Filter Media', 'Instrumentation', 'Valves and Fittings', 'Services']
  const updateFilter = <K extends keyof ProductFilters>(key: K, value: ProductFilters[K]) => setFilters((state) => ({ ...state, [key]: value, page: key === 'page' ? Number(value) : 1 }))
  const openCreate = () => { setEditingProduct(undefined); setIsFormOpen(true) }
  const openEdit = (product: Product) => { setSelectedProduct(undefined); setEditingProduct(product); setIsFormOpen(true) }
  const save = (values: ProductFormValues) => editingProduct ? updateMutation.mutate({ product: editingProduct, values }) : createMutation.mutate(values)

  return <div className="space-y-6"><PageHeader title="Product management" description="Maintain stock products, service items, units, and inventory monitoring status." actions={<Button onClick={openCreate}><PackagePlus aria-hidden="true" size={17} /> Create product</Button>} />{productsQuery.isPlaceholderData ? <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Preview products are shown until the product-management API is available.</div> : null}{error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}<section className="grid gap-3 rounded-xl border border-border bg-surface p-4 shadow-panel lg:grid-cols-[minmax(0,1fr)_190px_180px_160px]"><label className="relative block"><span className="sr-only">Search products</span><Search aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" size={18} /><input className="h-11 w-full rounded-lg border border-border bg-surface pl-10 pr-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" placeholder="Search by product, SKU, or barcode" value={filters.search} onChange={(event: ChangeEvent<HTMLInputElement>) => updateFilter('search', event.target.value)} /></label><select className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.category} onChange={(event) => updateFilter('category', event.target.value)}>{categories.map((category) => <option key={category} value={category}>{category === 'all' ? 'All categories' : category}</option>)}</select><select className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.stockStatus} onChange={(event) => updateFilter('stockStatus', event.target.value as StockStatus | 'all')}><option value="all">All stock states</option><option value="in_stock">In stock</option><option value="low_stock">Low stock</option><option value="critical_stock">Critical stock</option><option value="out_of_stock">Out of stock</option></select><select className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.active} onChange={(event) => updateFilter('active', event.target.value as ProductFilters['active'])}><option value="all">All states</option><option value="active">Active</option><option value="inactive">Inactive</option></select></section><div className="flex items-center justify-between text-sm text-muted"><p>{productsQuery.data?.meta.total ?? 0} products</p><p>{productsQuery.isFetching ? 'Updating…' : 'Server pagination enabled'}</p></div><ProductTable products={products} onEdit={openEdit} onView={setSelectedProduct} /><nav aria-label="Product pagination" className="flex items-center justify-between gap-3"><p className="text-sm text-muted">Page {filters.page} of {totalPages}</p><div className="flex gap-2"><Button disabled={filters.page <= 1} variant="secondary" onClick={() => updateFilter('page', filters.page - 1)}>Previous</Button><Button disabled={filters.page >= totalPages} variant="secondary" onClick={() => updateFilter('page', filters.page + 1)}>Next</Button></div></nav>{selectedProduct ? <ProductDetailsDrawer product={selectedProduct} onClose={() => setSelectedProduct(undefined)} onEdit={openEdit} /> : null}{isFormOpen ? <ProductFormDialog isSaving={createMutation.isPending || updateMutation.isPending} product={editingProduct} onClose={() => { setIsFormOpen(false); setEditingProduct(undefined) }} onSave={save} /> : null}</div>
}
