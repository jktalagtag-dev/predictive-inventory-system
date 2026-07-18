import { useEffect, useState } from 'react'
import { PlusCircle, RefreshCw } from 'lucide-react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/features/auth/AuthProvider'
import { useProductOptions } from '@/features/products/hooks/useProducts'
import {
  acknowledgeAlert,
  calculateEoq,
  createReorderPolicy,
  dismissAlert,
  evaluateRestockingAlerts,
  getRestockingAlert,
  recalculateRop,
  reorderPolicyQueryKeys,
  resolveAlert,
  restockingAlertQueryKeys,
} from '@/features/restocking/api/restockingApi'
import { AlertDetailsDrawer } from '@/features/restocking/components/AlertDetailsDrawer'
import { AlertTable } from '@/features/restocking/components/AlertTable'
import { ReorderPolicyDetailsDrawer } from '@/features/restocking/components/ReorderPolicyDetailsDrawer'
import { ReorderPolicyFormDialog } from '@/features/restocking/components/ReorderPolicyFormDialog'
import { ReorderPolicyTable } from '@/features/restocking/components/ReorderPolicyTable'
import { useReorderPolicies, useRestockingAlerts } from '@/features/restocking/hooks/useRestocking'
import type {
  AlertSeverity,
  AlertStatus,
  CreateReorderPolicyPayload,
  ReorderPolicy,
  ReorderPolicyFilters,
  RestockingAlertFilters,
} from '@/features/restocking/types/restocking'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

type Tab = 'policies' | 'alerts'

const tabs: { id: Tab; label: string }[] = [
  { id: 'policies', label: 'Reorder policies' },
  { id: 'alerts', label: 'Alerts' },
]

const defaultPolicyFilters: ReorderPolicyFilters = { branchId: null, page: 1, perPage: 10 }
const defaultAlertFilters: RestockingAlertFilters = { branchId: null, status: 'all', severity: 'all', page: 1, perPage: 10 }

export default function RestockingPage() {
  const { session, hasPermission } = useAuth()
  const [tab, setTab] = useState<Tab>('policies')
  const [policyFilters, setPolicyFilters] = useState<ReorderPolicyFilters>(defaultPolicyFilters)
  const [alertFilters, setAlertFilters] = useState<RestockingAlertFilters>(defaultAlertFilters)
  const [selectedPolicyId, setSelectedPolicyId] = useState<string | undefined>()
  const [selectedAlertId, setSelectedAlertId] = useState<string | undefined>()
  const [isFormOpen, setIsFormOpen] = useState(false)
  const queryClient = useQueryClient()

  const defaultBranchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id
  useEffect(() => {
    if (!defaultBranchId) return
    setPolicyFilters((state) => (state.branchId === defaultBranchId ? state : { ...state, branchId: defaultBranchId }))
    setAlertFilters((state) => (state.branchId === defaultBranchId ? state : { ...state, branchId: defaultBranchId }))
  }, [defaultBranchId])

  const policiesQuery = useReorderPolicies(policyFilters)
  const alertsQuery = useRestockingAlerts(alertFilters)
  const productOptionsQuery = useProductOptions()

  const selectedPolicy = policiesQuery.data?.data.find((policy) => policy.id === selectedPolicyId)
  const selectedAlertQuery = useQuery({
    queryKey: restockingAlertQueryKeys.detail(selectedAlertId ?? ''),
    queryFn: () => getRestockingAlert(selectedAlertId as string),
    enabled: selectedAlertId !== undefined,
  })

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: reorderPolicyQueryKeys.lists() })
    void queryClient.invalidateQueries({ queryKey: restockingAlertQueryKeys.lists() })
    if (selectedPolicyId) void queryClient.invalidateQueries({ queryKey: reorderPolicyQueryKeys.eoqHistory(selectedPolicyId) })
    if (selectedAlertId) void queryClient.invalidateQueries({ queryKey: restockingAlertQueryKeys.detail(selectedAlertId) })
  }

  const createPolicyMutation = useMutation({
    mutationFn: (payload: Omit<CreateReorderPolicyPayload, 'branchId'>) => createReorderPolicy({ ...payload, branchId: policyFilters.branchId as string }),
    onSuccess: () => { invalidate(); setIsFormOpen(false) },
  })
  const recalculateMutation = useMutation({ mutationFn: (policy: ReorderPolicy) => recalculateRop(policy), onSuccess: invalidate })
  const eoqMutation = useMutation({
    mutationFn: ({ policy, annualDemandQuantity, orderingCost, annualHoldingCostPerUnit }: { policy: ReorderPolicy; annualDemandQuantity: string; orderingCost: string; annualHoldingCostPerUnit: string }) =>
      calculateEoq(policy, annualDemandQuantity, orderingCost, annualHoldingCostPerUnit, 'PHP'),
    onSuccess: invalidate,
  })

  const evaluateMutation = useMutation({ mutationFn: () => evaluateRestockingAlerts(alertFilters.branchId as string), onSuccess: invalidate })
  const acknowledgeMutation = useMutation({ mutationFn: (payload: { alert: NonNullable<typeof selectedAlertQuery.data> }) => acknowledgeAlert(payload.alert), onSuccess: invalidate })
  const resolveMutation = useMutation({ mutationFn: (payload: { alert: NonNullable<typeof selectedAlertQuery.data>; reason: string }) => resolveAlert(payload.alert, payload.reason), onSuccess: invalidate })
  const dismissMutation = useMutation({ mutationFn: (payload: { alert: NonNullable<typeof selectedAlertQuery.data>; reason: string }) => dismissAlert(payload.alert, payload.reason), onSuccess: invalidate })

  const isActingOnAlert = acknowledgeMutation.isPending || resolveMutation.isPending || dismissMutation.isPending
  const error = (createPolicyMutation.error ?? recalculateMutation.error ?? eoqMutation.error ?? evaluateMutation.error ?? acknowledgeMutation.error ?? resolveMutation.error ?? dismissMutation.error) as ApiError | null

  const branchId = policyFilters.branchId

  return (
    <div className="space-y-6">
      <PageHeader
        actions={tab === 'policies' && hasPermission('planning.rop.manage') ? (
          <Button disabled={!branchId} onClick={() => setIsFormOpen(true)}><PlusCircle aria-hidden="true" size={17} /> New policy</Button>
        ) : tab === 'alerts' && hasPermission('restocking.evaluate') ? (
          <Button disabled={!branchId || evaluateMutation.isPending} onClick={() => evaluateMutation.mutate()}><RefreshCw aria-hidden="true" size={17} /> {evaluateMutation.isPending ? 'Evaluating…' : 'Evaluate now'}</Button>
        ) : undefined}
        description="Manage reorder points and review deduplicated restocking alerts."
        title="Restocking"
      />
      {error ? <div className="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <nav aria-label="Restocking sections" className="flex gap-1 border-b border-border">
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

      {tab === 'policies' ? (
        <div className="space-y-4">
          <p className="text-sm text-muted">{policiesQuery.data?.meta.total ?? 0} reorder policies {policiesQuery.isFetching ? '· Updating…' : ''}</p>
          <ReorderPolicyTable policies={policiesQuery.data?.data ?? []} onView={(policy) => setSelectedPolicyId(policy.id)} />
        </div>
      ) : null}

      {tab === 'alerts' ? (
        <div className="space-y-4">
          <section className="grid gap-3 rounded-card border border-border bg-surface p-4 shadow-panel sm:p-6 md:grid-cols-2">
            <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={alertFilters.status} onChange={(event) => setAlertFilters((state) => ({ ...state, status: event.target.value as AlertStatus | 'all', page: 1 }))}>
              <option value="all">All statuses</option>
              <option value="active">Active</option>
              <option value="acknowledged">Acknowledged</option>
              <option value="resolved">Resolved</option>
              <option value="dismissed">Dismissed</option>
            </select>
            <select className="h-11 rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={alertFilters.severity} onChange={(event) => setAlertFilters((state) => ({ ...state, severity: event.target.value as AlertSeverity | 'all', page: 1 }))}>
              <option value="all">All severities</option>
              <option value="critical">Critical</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
          </section>
          <p className="text-sm text-muted">{alertsQuery.data?.meta.total ?? 0} alerts {alertsQuery.isFetching ? '· Updating…' : ''}</p>
          <AlertTable alerts={alertsQuery.data?.data ?? []} onView={(alert) => setSelectedAlertId(alert.id)} />
        </div>
      ) : null}

      {isFormOpen ? (
        <ReorderPolicyFormDialog
          isSaving={createPolicyMutation.isPending}
          productOptions={productOptionsQuery.data ?? []}
          onClose={() => setIsFormOpen(false)}
          onSave={(payload) => createPolicyMutation.mutate(payload)}
        />
      ) : null}

      {selectedPolicy ? (
        <ReorderPolicyDetailsDrawer
          canCalculate={hasPermission('planning.rop.calculate')}
          canCalculateEoq={hasPermission('planning.eoq.calculate')}
          isActing={recalculateMutation.isPending || eoqMutation.isPending}
          policy={selectedPolicy}
          onCalculateEoq={(annualDemandQuantity, orderingCost, annualHoldingCostPerUnit) => eoqMutation.mutate({ policy: selectedPolicy, annualDemandQuantity, orderingCost, annualHoldingCostPerUnit })}
          onClose={() => setSelectedPolicyId(undefined)}
          onRecalculateRop={() => recalculateMutation.mutate(selectedPolicy)}
        />
      ) : null}

      {selectedAlertQuery.data ? (
        <AlertDetailsDrawer
          alert={selectedAlertQuery.data}
          isActing={isActingOnAlert}
          onAcknowledge={() => acknowledgeMutation.mutate({ alert: selectedAlertQuery.data })}
          onClose={() => setSelectedAlertId(undefined)}
          onDismiss={(reason) => dismissMutation.mutate({ alert: selectedAlertQuery.data, reason })}
          onResolve={(reason) => resolveMutation.mutate({ alert: selectedAlertQuery.data, reason })}
        />
      ) : null}
    </div>
  )
}
