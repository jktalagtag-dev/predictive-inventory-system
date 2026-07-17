import { useEffect, useState } from 'react'
import { PackagePlus } from 'lucide-react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/features/auth/AuthProvider'
import { useProductOptions } from '@/features/products/hooks/useProducts'
import {
  approveInventoryAdjustment,
  createInventoryAdjustment,
  getInventoryAdjustment,
  inventoryQueryKeys,
  postInventoryAdjustment,
  reverseInventoryAdjustment,
} from '@/features/inventory/api/inventoryApi'
import { AdjustmentDetailsDrawer } from '@/features/inventory/components/AdjustmentDetailsDrawer'
import { AdjustmentFormDialog } from '@/features/inventory/components/AdjustmentFormDialog'
import { AdjustmentTable } from '@/features/inventory/components/AdjustmentTable'
import { InventoryBalanceTable } from '@/features/inventory/components/InventoryBalanceTable'
import { InventoryMovementTable } from '@/features/inventory/components/InventoryMovementTable'
import { useInventoryAdjustments, useInventoryBalances, useInventoryMovements } from '@/features/inventory/hooks/useInventory'
import type {
  AdjustmentFormValues,
  AdjustmentStatus,
  InventoryAdjustment,
  InventoryAdjustmentFilters,
  InventoryBalanceFilters,
  InventoryMovementFilters,
  MovementType,
} from '@/features/inventory/types/inventory'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

type Tab = 'balances' | 'movements' | 'adjustments'

const tabs: { id: Tab; label: string }[] = [
  { id: 'balances', label: 'Balances' },
  { id: 'movements', label: 'Movement history' },
  { id: 'adjustments', label: 'Adjustments' },
]

const defaultBalanceFilters: InventoryBalanceFilters = { branchId: null, availability: 'all', search: '', page: 1, perPage: 10 }
const defaultMovementFilters: InventoryMovementFilters = { branchId: null, movementType: 'all', page: 1, perPage: 10 }
const defaultAdjustmentFilters: InventoryAdjustmentFilters = { branchId: null, status: 'all', page: 1, perPage: 10 }

export default function InventoryPage() {
  const { session, hasPermission } = useAuth()
  const [tab, setTab] = useState<Tab>('balances')
  const [balanceFilters, setBalanceFilters] = useState<InventoryBalanceFilters>(defaultBalanceFilters)
  const [movementFilters, setMovementFilters] = useState<InventoryMovementFilters>(defaultMovementFilters)
  const [adjustmentFilters, setAdjustmentFilters] = useState<InventoryAdjustmentFilters>(defaultAdjustmentFilters)
  const [selectedAdjustmentId, setSelectedAdjustmentId] = useState<string | undefined>()
  const [isFormOpen, setIsFormOpen] = useState(false)
  const queryClient = useQueryClient()

  const defaultBranchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id
  useEffect(() => {
    if (!defaultBranchId) return
    setBalanceFilters((state) => (state.branchId === defaultBranchId ? state : { ...state, branchId: defaultBranchId }))
    setMovementFilters((state) => (state.branchId === defaultBranchId ? state : { ...state, branchId: defaultBranchId }))
    setAdjustmentFilters((state) => (state.branchId === defaultBranchId ? state : { ...state, branchId: defaultBranchId }))
  }, [defaultBranchId])

  const balancesQuery = useInventoryBalances(balanceFilters)
  const movementsQuery = useInventoryMovements(movementFilters)
  const adjustmentsQuery = useInventoryAdjustments(adjustmentFilters)
  const productOptionsQuery = useProductOptions()
  const selectedAdjustmentQuery = useQuery({
    queryKey: inventoryQueryKeys.adjustmentDetail(selectedAdjustmentId ?? ''),
    queryFn: () => getInventoryAdjustment(selectedAdjustmentId as string),
    enabled: selectedAdjustmentId !== undefined,
  })

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: inventoryQueryKeys.adjustmentLists() })
    void queryClient.invalidateQueries({ queryKey: ['inventory-balances'] })
    void queryClient.invalidateQueries({ queryKey: ['inventory-movements'] })
    if (selectedAdjustmentId) void queryClient.invalidateQueries({ queryKey: inventoryQueryKeys.adjustmentDetail(selectedAdjustmentId) })
  }

  const createMutation = useMutation({
    mutationFn: (values: AdjustmentFormValues) => createInventoryAdjustment(adjustmentFilters.branchId as string, values),
    onSuccess: () => { invalidate(); setIsFormOpen(false) },
  })
  const approveMutation = useMutation({ mutationFn: (adjustment: InventoryAdjustment) => approveInventoryAdjustment(adjustment), onSuccess: invalidate })
  const postMutation = useMutation({ mutationFn: (adjustment: InventoryAdjustment) => postInventoryAdjustment(adjustment), onSuccess: invalidate })
  const reverseMutation = useMutation({ mutationFn: ({ adjustment, reason }: { adjustment: InventoryAdjustment; reason: string }) => reverseInventoryAdjustment(adjustment, reason), onSuccess: invalidate })

  const isActing = approveMutation.isPending || postMutation.isPending || reverseMutation.isPending
  const error = (createMutation.error ?? approveMutation.error ?? postMutation.error ?? reverseMutation.error) as ApiError | null

  const branchId = balanceFilters.branchId

  return (
    <div className="space-y-6">
      <PageHeader
        title="Inventory"
        description="Monitor stock, review movement history, and manage inventory adjustments for your branch."
        actions={tab === 'adjustments' && hasPermission('inventory.adjustments.create') ? (
          <Button disabled={!branchId} onClick={() => setIsFormOpen(true)}><PackagePlus aria-hidden="true" size={17} /> Create adjustment</Button>
        ) : undefined}
      />
      {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <nav aria-label="Inventory sections" className="flex gap-1 border-b border-border">
        {tabs.map((item) => (
          <button
            key={item.id}
            className={`border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors ${tab === item.id ? 'border-brand-600 text-brand-700' : 'border-transparent text-muted hover:text-ink'}`}
            type="button"
            onClick={() => setTab(item.id)}
          >
            {item.label}
          </button>
        ))}
      </nav>

      {tab === 'balances' ? (
        <div className="space-y-4">
          <section className="grid gap-3 rounded-xl border border-border bg-surface p-4 shadow-panel md:grid-cols-[minmax(0,1fr)_180px]">
            <input className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" placeholder="Search by product name or SKU" value={balanceFilters.search} onChange={(event) => setBalanceFilters((state) => ({ ...state, search: event.target.value, page: 1 }))} />
            <select className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={balanceFilters.availability} onChange={(event) => setBalanceFilters((state) => ({ ...state, availability: event.target.value as InventoryBalanceFilters['availability'], page: 1 }))}>
              <option value="all">All availability</option>
              <option value="in_stock">In stock</option>
              <option value="out_of_stock">Out of stock</option>
            </select>
          </section>
          <p className="text-sm text-muted">{balancesQuery.data?.meta.total ?? 0} products {balancesQuery.isFetching ? '· Updating…' : ''}</p>
          <InventoryBalanceTable balances={balancesQuery.data?.data ?? []} />
        </div>
      ) : null}

      {tab === 'movements' ? (
        <div className="space-y-4">
          <section className="grid gap-3 rounded-xl border border-border bg-surface p-4 shadow-panel md:grid-cols-[220px]">
            <select className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={movementFilters.movementType} onChange={(event) => setMovementFilters((state) => ({ ...state, movementType: event.target.value as MovementType | 'all', page: 1 }))}>
              <option value="all">All movement types</option>
              <option value="receipt">Receipt</option>
              <option value="sale">Sale</option>
              <option value="adjustment">Adjustment</option>
              <option value="return">Return</option>
              <option value="reservation">Reservation</option>
              <option value="release">Release</option>
              <option value="reversal">Reversal</option>
            </select>
          </section>
          <p className="text-sm text-muted">{movementsQuery.data?.meta.total ?? 0} movements {movementsQuery.isFetching ? '· Updating…' : ''}</p>
          <InventoryMovementTable movements={movementsQuery.data?.data ?? []} />
        </div>
      ) : null}

      {tab === 'adjustments' ? (
        <div className="space-y-4">
          <section className="grid gap-3 rounded-xl border border-border bg-surface p-4 shadow-panel md:grid-cols-[220px]">
            <select className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={adjustmentFilters.status} onChange={(event) => setAdjustmentFilters((state) => ({ ...state, status: event.target.value as AdjustmentStatus | 'all', page: 1 }))}>
              <option value="all">All statuses</option>
              <option value="pending_approval">Pending approval</option>
              <option value="posted">Posted</option>
              <option value="reversed">Reversed</option>
            </select>
          </section>
          <p className="text-sm text-muted">{adjustmentsQuery.data?.meta.total ?? 0} adjustments {adjustmentsQuery.isFetching ? '· Updating…' : ''}</p>
          <AdjustmentTable adjustments={adjustmentsQuery.data?.data ?? []} onView={(adjustment) => setSelectedAdjustmentId(adjustment.id)} />
        </div>
      ) : null}

      {isFormOpen ? (
        <AdjustmentFormDialog isSaving={createMutation.isPending} productOptions={productOptionsQuery.data ?? []} onClose={() => setIsFormOpen(false)} onSave={(values) => createMutation.mutate(values)} />
      ) : null}
      {selectedAdjustmentQuery.data ? (
        <AdjustmentDetailsDrawer
          adjustment={selectedAdjustmentQuery.data}
          isActing={isActing}
          onApprove={() => approveMutation.mutate(selectedAdjustmentQuery.data)}
          onClose={() => setSelectedAdjustmentId(undefined)}
          onPost={() => postMutation.mutate(selectedAdjustmentQuery.data)}
          onReverse={(reason) => reverseMutation.mutate({ adjustment: selectedAdjustmentQuery.data, reason })}
        />
      ) : null}
    </div>
  )
}
