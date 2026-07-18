import { type ChangeEvent, useEffect, useState } from 'react'
import { FilePlus2, Search } from 'lucide-react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/features/auth/AuthProvider'
import { useProductOptions, useUnitOptions } from '@/features/products/hooks/useProducts'
import { useSupplierOptions } from '@/features/suppliers/hooks/useSuppliers'
import {
  cancelPurchaseOrder,
  closePurchaseOrder,
  createPurchaseOrder,
  decidePurchaseOrder,
  getPurchaseOrder,
  markPurchaseOrderOrdered,
  purchaseOrderQueryKeys,
  submitPurchaseOrder,
} from '@/features/purchase-orders/api/purchaseOrdersApi'
import { PurchaseOrderDetailsDrawer } from '@/features/purchase-orders/components/PurchaseOrderDetailsDrawer'
import { PurchaseOrderFormDialog } from '@/features/purchase-orders/components/PurchaseOrderFormDialog'
import { PurchaseOrderTable } from '@/features/purchase-orders/components/PurchaseOrderTable'
import { usePurchaseOrders } from '@/features/purchase-orders/hooks/usePurchaseOrders'
import type { PurchaseOrder, PurchaseOrderFilters, PurchaseOrderFormValues, PurchaseOrderStatus } from '@/features/purchase-orders/types/purchaseOrder'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

const defaultFilters: PurchaseOrderFilters = { branchId: null, supplierId: 'all', status: 'all', search: '', page: 1, perPage: 10 }

export default function PurchaseOrdersPage() {
  const { session } = useAuth()
  const [filters, setFilters] = useState<PurchaseOrderFilters>(defaultFilters)
  const [selectedId, setSelectedId] = useState<string | undefined>()
  const [isFormOpen, setIsFormOpen] = useState(false)
  const queryClient = useQueryClient()

  const defaultBranchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id
  useEffect(() => {
    if (defaultBranchId && filters.branchId !== defaultBranchId) {
      setFilters((state) => ({ ...state, branchId: defaultBranchId }))
    }
  }, [defaultBranchId, filters.branchId])

  const poQuery = usePurchaseOrders(filters)
  const supplierOptionsQuery = useSupplierOptions()
  const productOptionsQuery = useProductOptions()
  const unitOptionsQuery = useUnitOptions()
  const selectedQuery = useQuery({ queryKey: purchaseOrderQueryKeys.detail(selectedId ?? ''), queryFn: () => getPurchaseOrder(selectedId as string), enabled: selectedId !== undefined })

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: purchaseOrderQueryKeys.lists() })
    if (selectedId) void queryClient.invalidateQueries({ queryKey: purchaseOrderQueryKeys.detail(selectedId) })
  }

  const createMutation = useMutation({
    mutationFn: (values: PurchaseOrderFormValues) => createPurchaseOrder(filters.branchId as string, values),
    onSuccess: () => { invalidate(); setIsFormOpen(false) },
  })
  const submitMutation = useMutation({ mutationFn: (po: PurchaseOrder) => submitPurchaseOrder(po), onSuccess: invalidate })
  const decideMutation = useMutation({ mutationFn: ({ po, decision, reason }: { po: PurchaseOrder; decision: 'approved' | 'rejected'; reason?: string }) => decidePurchaseOrder(po, decision, reason), onSuccess: invalidate })
  const orderMutation = useMutation({ mutationFn: (po: PurchaseOrder) => markPurchaseOrderOrdered(po, new Date().toISOString()), onSuccess: invalidate })
  const cancelMutation = useMutation({ mutationFn: ({ po, reason }: { po: PurchaseOrder; reason: string }) => cancelPurchaseOrder(po, reason), onSuccess: invalidate })
  const closeMutation = useMutation({ mutationFn: (po: PurchaseOrder) => closePurchaseOrder(po), onSuccess: invalidate })

  const isActing = submitMutation.isPending || decideMutation.isPending || orderMutation.isPending || cancelMutation.isPending || closeMutation.isPending

  const totalPages = Math.max(1, Math.ceil((poQuery.data?.meta.total ?? 0) / filters.perPage))
  const error = (createMutation.error ?? submitMutation.error ?? decideMutation.error ?? orderMutation.error ?? cancelMutation.error ?? closeMutation.error ?? poQuery.error) as ApiError | null
  const purchaseOrders = poQuery.data?.data ?? []
  const supplierOptions = supplierOptionsQuery.data ?? []
  const productOptions = productOptionsQuery.data ?? []
  const unitOptions = unitOptionsQuery.data ?? []

  const updateFilter = <K extends keyof PurchaseOrderFilters>(key: K, value: PurchaseOrderFilters[K]) => setFilters((state) => ({ ...state, [key]: value, page: key === 'page' ? Number(value) : 1 }))

  return (
    <div className="space-y-6">
      <PageHeader title="Purchase orders" description="Draft, submit, and approve purchase orders for your branch." actions={<Button disabled={!filters.branchId} onClick={() => setIsFormOpen(true)}><FilePlus2 aria-hidden="true" size={17} /> Create purchase order</Button>} />
      {error ? <div className="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <section className="grid gap-3 rounded-card border border-border bg-surface p-4 shadow-panel sm:p-6 md:grid-cols-[minmax(0,1fr)_180px_180px]">
        <label className="relative block"><span className="sr-only">Search by PO number</span><Search aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" size={18} /><input className="h-11 w-full rounded-xl border border-border bg-surface pl-10 pr-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" placeholder="Search by PO number" value={filters.search} onChange={(event: ChangeEvent<HTMLInputElement>) => updateFilter('search', event.target.value)} /></label>
        <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.supplierId} onChange={(event) => updateFilter('supplierId', event.target.value)}>
          <option value="all">All suppliers</option>
          {supplierOptions.map((option) => <option key={option.id} value={option.id}>{option.legalName}</option>)}
        </select>
        <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.status} onChange={(event) => updateFilter('status', event.target.value as PurchaseOrderStatus | 'all')}>
          <option value="all">All statuses</option>
          <option value="draft">Draft</option>
          <option value="submitted">Submitted</option>
          <option value="approved">Approved</option>
          <option value="ordered">Ordered</option>
          <option value="cancelled">Cancelled</option>
          <option value="closed">Closed</option>
        </select>
      </section>

      <div className="flex items-center justify-between text-sm text-muted"><p>{poQuery.data?.meta.total ?? 0} purchase orders</p><p>{poQuery.isFetching ? 'Updating…' : 'Server pagination enabled'}</p></div>
      <PurchaseOrderTable purchaseOrders={purchaseOrders} onView={(po) => setSelectedId(po.id)} />

      <nav aria-label="Purchase order pagination" className="flex items-center justify-between gap-3"><p className="text-sm text-muted">Page {filters.page} of {totalPages}</p><div className="flex gap-2"><Button disabled={filters.page <= 1} variant="secondary" onClick={() => updateFilter('page', filters.page - 1)}>Previous</Button><Button disabled={filters.page >= totalPages} variant="secondary" onClick={() => updateFilter('page', filters.page + 1)}>Next</Button></div></nav>

      {isFormOpen ? <PurchaseOrderFormDialog isSaving={createMutation.isPending} productOptions={productOptions} supplierOptions={supplierOptions} unitOptions={unitOptions} onClose={() => setIsFormOpen(false)} onSave={(values) => createMutation.mutate(values)} /> : null}
      {selectedQuery.data ? (
        <PurchaseOrderDetailsDrawer
          isActing={isActing}
          purchaseOrder={selectedQuery.data}
          onApprove={() => decideMutation.mutate({ po: selectedQuery.data, decision: 'approved' })}
          onCancel={(reason) => cancelMutation.mutate({ po: selectedQuery.data, reason })}
          onClose={() => setSelectedId(undefined)}
          onClosePo={() => closeMutation.mutate(selectedQuery.data)}
          onMarkOrdered={() => orderMutation.mutate(selectedQuery.data)}
          onReject={(reason) => decideMutation.mutate({ po: selectedQuery.data, decision: 'rejected', reason })}
          onSubmit={() => submitMutation.mutate(selectedQuery.data)}
        />
      ) : null}
    </div>
  )
}
