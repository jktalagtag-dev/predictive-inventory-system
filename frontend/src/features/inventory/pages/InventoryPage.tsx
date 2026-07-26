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
import { getCachedProducts } from '@/shared/offline/productCache'
import { syncCoordinator } from '@/shared/offline/syncCoordinator'
import { useOnlineStatus } from '@/shared/offline/useOnlineStatus'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'
import { useToast } from '@/shared/components/Toast'

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
  const [queuedMessage, setQueuedMessage] = useState<string | null>(null)
  const queryClient = useQueryClient()
  const isOnline = useOnlineStatus()
  const { toast } = useToast()

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
  const offlineProductOptionsQuery = useQuery({
    queryKey: ['offline-product-cache', session?.user.id],
    queryFn: () => getCachedProducts(session?.user.id as string),
    enabled: !isOnline && session?.user.id !== undefined,
    // This reads IndexedDB, not the network — without this, TanStack
    // Query's own online-manager (which listens to the same native
    // online/offline events as useOnlineStatus) pauses the query
    // whenever the browser is offline, which is exactly when it's
    // needed.
    networkMode: 'always',
  })
  const productOptions = isOnline ? (productOptionsQuery.data ?? []) : (offlineProductOptionsQuery.data ?? [])
  const selectedAdjustmentQuery = useQuery({
    queryKey: inventoryQueryKeys.adjustmentDetail(selectedAdjustmentId ?? ''),
    queryFn: () => getInventoryAdjustment(selectedAdjustmentId as string),
    enabled: selectedAdjustmentId !== undefined && isOnline,
  })

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: inventoryQueryKeys.adjustmentLists() })
    void queryClient.invalidateQueries({ queryKey: ['inventory-balances'] })
    void queryClient.invalidateQueries({ queryKey: ['inventory-movements'] })
    if (selectedAdjustmentId) void queryClient.invalidateQueries({ queryKey: inventoryQueryKeys.adjustmentDetail(selectedAdjustmentId) })
  }

  const createMutation = useMutation({
    mutationFn: async (values: AdjustmentFormValues): Promise<InventoryAdjustment | null> => {
      const branchId = adjustmentFilters.branchId as string

      if (!isOnline) {
        const userId = session?.user.id
        if (!userId) throw new Error('No active session to queue this adjustment against.')

        // Queued, never shown as a finalized record — the Adjustments
        // list only ever reflects server-accepted state
        // (DEVELOPMENT_ROADMAP.md M9 acceptance criteria).
        await syncCoordinator.enqueue({
          clientOperationId: crypto.randomUUID(),
          userId,
          operationType: 'inventory_adjustment.create',
          branchId,
          payloadVersion: 1,
          idempotencyKey: crypto.randomUUID(),
          dependencyOperationId: null,
          payload: {
            reasonCode: values.reasonCode,
            reasonNote: values.reasonNote || undefined,
            effectiveAt: values.effectiveAt,
            lines: values.lines.map((line) => ({
              productId: line.productId,
              quantityDelta: line.quantityDelta,
              unitCost: line.unitCost || undefined,
              notes: line.notes || undefined,
            })),
          },
          summary: `Adjustment: ${values.lines.length} line${values.lines.length === 1 ? '' : 's'} (${values.reasonCode})`,
        })
        return null
      }

      return createInventoryAdjustment(branchId, values)
    },
    onSuccess: (result) => {
      if (result === null) {
        setQueuedMessage('Adjustment queued on this device — it will sync automatically once you are back online.')
      } else {
        invalidate()
      }
      setIsFormOpen(false)
    },
    // The offline branch only writes to IndexedDB; without this,
    // TanStack Query's online-manager pauses the mutation entirely while
    // the browser is offline, so it would never even run.
    networkMode: 'always',
  })
  const approveMutation = useMutation({
    mutationFn: (adjustment: InventoryAdjustment) => approveInventoryAdjustment(adjustment),
    onSuccess: (adjustment) => { invalidate(); toast({ title: 'Adjustment approved', description: adjustment.adjustmentNumber, variant: 'success' }) },
  })
  const postMutation = useMutation({
    mutationFn: (adjustment: InventoryAdjustment) => postInventoryAdjustment(adjustment),
    onSuccess: (adjustment) => { invalidate(); toast({ title: 'Adjustment posted', description: adjustment.adjustmentNumber, variant: 'success' }) },
  })
  const reverseMutation = useMutation({
    mutationFn: ({ adjustment, reason }: { adjustment: InventoryAdjustment; reason: string }) => reverseInventoryAdjustment(adjustment, reason),
    onSuccess: (adjustment) => { invalidate(); toast({ title: 'Adjustment reversed', description: adjustment.adjustmentNumber, variant: 'success' }) },
  })

  const isActing = approveMutation.isPending || postMutation.isPending || reverseMutation.isPending
  const error = (createMutation.error ?? approveMutation.error ?? postMutation.error ?? reverseMutation.error) as ApiError | null

  const branchId = balanceFilters.branchId

  return (
    <div className="space-y-6">
      <PageHeader
        title="Inventory"
        description="Monitor stock, review movement history, and manage inventory adjustments for your branch."
        actions={tab === 'adjustments' && hasPermission('inventory.adjustments.create') ? (
          <Button disabled={!branchId} onClick={() => { setQueuedMessage(null); setIsFormOpen(true) }}><PackagePlus aria-hidden="true" size={18} /> Create adjustment</Button>
        ) : undefined}
      />
      {!isOnline ? <div className="rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning-text" role="status">You are offline. Adjustment drafts you create now will queue and sync automatically once connectivity returns.</div> : null}
      {queuedMessage ? <div className="rounded-xl border border-info/30 bg-info/10 px-4 py-3 text-sm text-info-text" role="status">{queuedMessage}</div> : null}
      {error ? <div className="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

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
          <section className="grid gap-3 rounded-card border border-border bg-surface p-4 shadow-panel sm:p-6 md:grid-cols-[minmax(0,1fr)_180px]">
            <input className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" placeholder="Search by product name or SKU" value={balanceFilters.search} onChange={(event) => setBalanceFilters((state) => ({ ...state, search: event.target.value, page: 1 }))} />
            <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={balanceFilters.availability} onChange={(event) => setBalanceFilters((state) => ({ ...state, availability: event.target.value as InventoryBalanceFilters['availability'], page: 1 }))}>
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
          <section className="grid gap-3 rounded-card border border-border bg-surface p-4 shadow-panel sm:p-6 md:grid-cols-[220px]">
            <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={movementFilters.movementType} onChange={(event) => setMovementFilters((state) => ({ ...state, movementType: event.target.value as MovementType | 'all', page: 1 }))}>
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
          <section className="grid gap-3 rounded-card border border-border bg-surface p-4 shadow-panel sm:p-6 md:grid-cols-[220px]">
            <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={adjustmentFilters.status} onChange={(event) => setAdjustmentFilters((state) => ({ ...state, status: event.target.value as AdjustmentStatus | 'all', page: 1 }))}>
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
        <AdjustmentFormDialog isSaving={createMutation.isPending} productOptions={productOptions} onClose={() => setIsFormOpen(false)} onSave={(values) => createMutation.mutate(values)} />
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
