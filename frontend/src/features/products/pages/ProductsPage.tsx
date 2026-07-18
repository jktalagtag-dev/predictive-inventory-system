import { type ChangeEvent, useEffect, useState } from 'react'
import { PackagePlus, Search } from 'lucide-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { archiveProduct, createProduct, productQueryKeys, updateProduct } from '@/features/products/api/productsApi'
import { ArchiveProductDialog } from '@/features/products/components/ArchiveProductDialog'
import { ProductDetailsDrawer } from '@/features/products/components/ProductDetailsDrawer'
import { ProductFormDialog } from '@/features/products/components/ProductFormDialog'
import { ProductTable } from '@/features/products/components/ProductTable'
import { useCategoryOptions, useProducts, useUnitOptions } from '@/features/products/hooks/useProducts'
import type { Product, ProductFilters, ProductFormValues, ProductType } from '@/features/products/types/product'
import { useAuth } from '@/features/auth/AuthProvider'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

const defaultFilters: ProductFilters = { search: '', categoryId: 'all', productType: 'all', active: 'all', branchId: null, page: 1, perPage: 10 }

export default function ProductsPage() {
  const { session } = useAuth()
  const [filters, setFilters] = useState<ProductFilters>(defaultFilters)
  const defaultBranchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id

  useEffect(() => {
    if (defaultBranchId && filters.branchId !== defaultBranchId) {
      setFilters((state) => ({ ...state, branchId: defaultBranchId }))
    }
  }, [defaultBranchId, filters.branchId])
  const [selectedProduct, setSelectedProduct] = useState<Product | undefined>()
  const [editingProduct, setEditingProduct] = useState<Product | undefined>()
  const [archivingProduct, setArchivingProduct] = useState<Product | undefined>()
  const [isFormOpen, setIsFormOpen] = useState(false)
  const queryClient = useQueryClient()
  const productsQuery = useProducts(filters)
  const categoryOptionsQuery = useCategoryOptions()
  const unitOptionsQuery = useUnitOptions()

  const invalidate = () => queryClient.invalidateQueries({ queryKey: productQueryKeys.lists() })
  const createMutation = useMutation({ mutationFn: createProduct, onSuccess: () => { void invalidate(); setIsFormOpen(false) } })
  const updateMutation = useMutation({ mutationFn: ({ product, values }: { product: Product; values: ProductFormValues }) => updateProduct(product, values), onSuccess: () => { void invalidate(); setIsFormOpen(false); setEditingProduct(undefined) } })
  const archiveMutation = useMutation({ mutationFn: archiveProduct, onSuccess: () => { void invalidate(); setArchivingProduct(undefined) } })

  const totalPages = Math.max(1, Math.ceil((productsQuery.data?.meta.total ?? 0) / filters.perPage))
  const error = (createMutation.error ?? updateMutation.error ?? archiveMutation.error ?? productsQuery.error) as ApiError | null
  const products = productsQuery.data?.data ?? []
  const categoryOptions = categoryOptionsQuery.data ?? []
  const unitOptions = unitOptionsQuery.data ?? []

  const updateFilter = <K extends keyof ProductFilters>(key: K, value: ProductFilters[K]) => setFilters((state) => ({ ...state, [key]: value, page: key === 'page' ? Number(value) : 1 }))
  const openCreate = () => { setEditingProduct(undefined); setIsFormOpen(true) }
  const openEdit = (product: Product) => { setSelectedProduct(undefined); setEditingProduct(product); setIsFormOpen(true) }
  const save = (values: ProductFormValues) => editingProduct ? updateMutation.mutate({ product: editingProduct, values }) : createMutation.mutate(values)

  return (
    <div className="space-y-6">
      <PageHeader title="Product management" description="Maintain stock products, service items, and units. Inventory monitoring is not yet available." actions={<Button onClick={openCreate}><PackagePlus aria-hidden="true" size={17} /> Create product</Button>} />
      {error ? <div className="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <section className="grid gap-3 rounded-card border border-border bg-surface p-4 shadow-panel sm:p-6 lg:grid-cols-[minmax(0,1fr)_190px_180px_160px]">
        <label className="relative block"><span className="sr-only">Search products</span><Search aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" size={18} /><input className="h-11 w-full rounded-xl border border-border bg-surface pl-10 pr-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" placeholder="Search by product, SKU, or barcode" value={filters.search} onChange={(event: ChangeEvent<HTMLInputElement>) => updateFilter('search', event.target.value)} /></label>
        <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.categoryId} onChange={(event) => updateFilter('categoryId', event.target.value)}>
          <option value="all">All categories</option>
          {categoryOptions.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
        </select>
        <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.productType} onChange={(event) => updateFilter('productType', event.target.value as ProductType | 'all')}>
          <option value="all">All product types</option>
          <option value="stock">Stock product</option>
          <option value="non_stock">Non-stock product</option>
          <option value="service">Service</option>
        </select>
        <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.active} onChange={(event) => updateFilter('active', event.target.value as ProductFilters['active'])}>
          <option value="all">All states</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </section>

      <div className="flex items-center justify-between text-sm text-muted"><p>{productsQuery.data?.meta.total ?? 0} products</p><p>{productsQuery.isFetching ? 'Updating…' : 'Server pagination enabled'}</p></div>
      <ProductTable products={products} onArchive={setArchivingProduct} onEdit={openEdit} onView={setSelectedProduct} />

      <nav aria-label="Product pagination" className="flex items-center justify-between gap-3"><p className="text-sm text-muted">Page {filters.page} of {totalPages}</p><div className="flex gap-2"><Button disabled={filters.page <= 1} variant="secondary" onClick={() => updateFilter('page', filters.page - 1)}>Previous</Button><Button disabled={filters.page >= totalPages} variant="secondary" onClick={() => updateFilter('page', filters.page + 1)}>Next</Button></div></nav>

      {selectedProduct ? <ProductDetailsDrawer product={selectedProduct} onClose={() => setSelectedProduct(undefined)} onEdit={openEdit} /> : null}
      {isFormOpen ? <ProductFormDialog categoryOptions={categoryOptions} isSaving={createMutation.isPending || updateMutation.isPending} product={editingProduct} unitOptions={unitOptions} onClose={() => { setIsFormOpen(false); setEditingProduct(undefined) }} onSave={save} /> : null}
      {archivingProduct ? <ArchiveProductDialog isArchiving={archiveMutation.isPending} product={archivingProduct} onClose={() => setArchivingProduct(undefined)} onConfirm={() => archiveMutation.mutate(archivingProduct)} /> : null}
    </div>
  )
}
