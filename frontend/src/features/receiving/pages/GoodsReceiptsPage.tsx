import { useEffect, useState } from 'react'
import { PackagePlus } from 'lucide-react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/features/auth/AuthProvider'
import { usePurchaseOrders } from '@/features/purchase-orders/hooks/usePurchaseOrders'
import {
  createGoodsReceipt,
  getGoodsReceipt,
  goodsReceiptQueryKeys,
  postGoodsReceipt,
  reverseGoodsReceipt,
} from '@/features/receiving/api/goodsReceiptsApi'
import { GoodsReceiptDetailsDrawer } from '@/features/receiving/components/GoodsReceiptDetailsDrawer'
import { GoodsReceiptFormDialog } from '@/features/receiving/components/GoodsReceiptFormDialog'
import { GoodsReceiptTable } from '@/features/receiving/components/GoodsReceiptTable'
import { useGoodsReceipts } from '@/features/receiving/hooks/useGoodsReceipts'
import type { GoodsReceipt, GoodsReceiptFilters, GoodsReceiptFormValues, GoodsReceiptStatus } from '@/features/receiving/types/goodsReceipt'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

const defaultFilters: GoodsReceiptFilters = { branchId: null, purchaseOrderId: 'all', status: 'all', page: 1, perPage: 10 }

export default function GoodsReceiptsPage() {
  const { session } = useAuth()
  const [filters, setFilters] = useState<GoodsReceiptFilters>(defaultFilters)
  const [selectedId, setSelectedId] = useState<string | undefined>()
  const [isFormOpen, setIsFormOpen] = useState(false)
  const queryClient = useQueryClient()

  const defaultBranchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id
  useEffect(() => {
    if (defaultBranchId && filters.branchId !== defaultBranchId) {
      setFilters((state) => ({ ...state, branchId: defaultBranchId }))
    }
  }, [defaultBranchId, filters.branchId])

  const receiptsQuery = useGoodsReceipts(filters)
  const receivablePoQuery = usePurchaseOrders({ branchId: filters.branchId, supplierId: 'all', status: 'all', search: '', page: 1, perPage: 100 })
  const selectedQuery = useQuery({ queryKey: goodsReceiptQueryKeys.detail(selectedId ?? ''), queryFn: () => getGoodsReceipt(selectedId as string), enabled: selectedId !== undefined })

  const receivablePurchaseOrders = (receivablePoQuery.data?.data ?? []).filter((po) => po.status === 'ordered' || po.status === 'partially_received')

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: goodsReceiptQueryKeys.lists() })
    void queryClient.invalidateQueries({ queryKey: ['purchase-orders'] })
    if (selectedId) void queryClient.invalidateQueries({ queryKey: goodsReceiptQueryKeys.detail(selectedId) })
  }

  const createMutation = useMutation({
    mutationFn: (values: GoodsReceiptFormValues) => createGoodsReceipt(filters.branchId as string, values),
    onSuccess: () => { invalidate(); setIsFormOpen(false) },
  })
  const postMutation = useMutation({ mutationFn: (receipt: GoodsReceipt) => postGoodsReceipt(receipt), onSuccess: invalidate })
  const reverseMutation = useMutation({ mutationFn: ({ receipt, reason }: { receipt: GoodsReceipt; reason: string }) => reverseGoodsReceipt(receipt, reason), onSuccess: invalidate })

  const isActing = postMutation.isPending || reverseMutation.isPending

  const totalPages = Math.max(1, Math.ceil((receiptsQuery.data?.meta.total ?? 0) / filters.perPage))
  const error = (createMutation.error ?? postMutation.error ?? reverseMutation.error ?? receiptsQuery.error) as ApiError | null
  const goodsReceipts = receiptsQuery.data?.data ?? []

  const updateFilter = <K extends keyof GoodsReceiptFilters>(key: K, value: GoodsReceiptFilters[K]) => setFilters((state) => ({ ...state, [key]: value, page: key === 'page' ? Number(value) : 1 }))

  return (
    <div className="space-y-6">
      <PageHeader
        title="Goods receiving"
        description="Record deliveries against ordered purchase orders and post them into stock."
        actions={<Button disabled={!filters.branchId || receivablePurchaseOrders.length === 0} onClick={() => setIsFormOpen(true)}><PackagePlus aria-hidden="true" size={18} /> Record receipt</Button>}
      />
      {error ? <div className="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <section className="grid gap-3 rounded-card border border-border bg-surface p-4 shadow-panel sm:p-6 md:grid-cols-[180px_minmax(0,1fr)]">
        <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.status} onChange={(event) => updateFilter('status', event.target.value as GoodsReceiptStatus | 'all')}>
          <option value="all">All statuses</option>
          <option value="draft">Draft</option>
          <option value="posted">Posted</option>
          <option value="reversed">Reversed</option>
        </select>
        <p className="flex items-center text-sm text-muted">{receiptsQuery.data?.meta.total ?? 0} goods receipts {receiptsQuery.isFetching ? '· Updating…' : ''}</p>
      </section>

      <GoodsReceiptTable goodsReceipts={goodsReceipts} onView={(receipt) => setSelectedId(receipt.id)} />

      <nav aria-label="Goods receipt pagination" className="flex items-center justify-between gap-3"><p className="text-sm text-muted">Page {filters.page} of {totalPages}</p><div className="flex gap-2"><Button disabled={filters.page <= 1} variant="secondary" onClick={() => updateFilter('page', filters.page - 1)}>Previous</Button><Button disabled={filters.page >= totalPages} variant="secondary" onClick={() => updateFilter('page', filters.page + 1)}>Next</Button></div></nav>

      {isFormOpen ? <GoodsReceiptFormDialog isSaving={createMutation.isPending} receivablePurchaseOrders={receivablePurchaseOrders} onClose={() => setIsFormOpen(false)} onSave={(values) => createMutation.mutate(values)} /> : null}
      {selectedQuery.data ? (
        <GoodsReceiptDetailsDrawer
          goodsReceipt={selectedQuery.data}
          isActing={isActing}
          onClose={() => setSelectedId(undefined)}
          onPost={() => postMutation.mutate(selectedQuery.data)}
          onReverse={(reason) => reverseMutation.mutate({ receipt: selectedQuery.data, reason })}
        />
      ) : null}
    </div>
  )
}
