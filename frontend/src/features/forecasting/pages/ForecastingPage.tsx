import { useEffect, useState } from 'react'
import { PlayCircle } from 'lucide-react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/features/auth/AuthProvider'
import { createForecastRun, forecastQueryKeys, getForecastRun, recordManualPlan } from '@/features/forecasting/api/forecastApi'
import { ForecastRunDetailsDrawer } from '@/features/forecasting/components/ForecastRunDetailsDrawer'
import { ForecastRunFormDialog } from '@/features/forecasting/components/ForecastRunFormDialog'
import { ForecastRunTable } from '@/features/forecasting/components/ForecastRunTable'
import { useForecastRuns } from '@/features/forecasting/hooks/useForecast'
import type { CreateForecastRunPayload, ForecastRunFilters, ForecastRunItem } from '@/features/forecasting/types/forecast'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

const defaultFilters: ForecastRunFilters = { branchId: null, page: 1, perPage: 10 }

export default function ForecastingPage() {
  const { session, hasPermission } = useAuth()
  const [filters, setFilters] = useState<ForecastRunFilters>(defaultFilters)
  const [selectedRunId, setSelectedRunId] = useState<string | undefined>()
  const [isFormOpen, setIsFormOpen] = useState(false)
  const queryClient = useQueryClient()

  const defaultBranchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id
  useEffect(() => {
    if (!defaultBranchId) return
    setFilters((state) => (state.branchId === defaultBranchId ? state : { ...state, branchId: defaultBranchId }))
  }, [defaultBranchId])

  const runsQuery = useForecastRuns(filters)
  const selectedRunQuery = useQuery({
    queryKey: forecastQueryKeys.detail(selectedRunId ?? ''),
    queryFn: () => getForecastRun(selectedRunId as string),
    enabled: selectedRunId !== undefined,
  })

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: forecastQueryKeys.lists() })
    if (selectedRunId) void queryClient.invalidateQueries({ queryKey: forecastQueryKeys.detail(selectedRunId) })
  }

  const createMutation = useMutation({
    mutationFn: (payload: Omit<CreateForecastRunPayload, 'branchId' | 'modelCode'>) =>
      createForecastRun({ ...payload, branchId: filters.branchId as string, modelCode: 'sma' }),
    onSuccess: (run) => { invalidate(); setIsFormOpen(false); setSelectedRunId(run.id) },
  })

  const manualPlanMutation = useMutation({
    mutationFn: ({ item, manualQuantity, reason, expiresAt }: { item: ForecastRunItem; manualQuantity: string; reason: string; expiresAt: string }) =>
      recordManualPlan(selectedRunId as string, item.productId, manualQuantity, reason, expiresAt),
    onSuccess: invalidate,
  })

  const error = (createMutation.error ?? manualPlanMutation.error) as ApiError | null
  const branchId = filters.branchId

  return (
    <div className="space-y-6">
      <PageHeader
        actions={hasPermission('forecasting.run') ? <Button disabled={!branchId} onClick={() => setIsFormOpen(true)}><PlayCircle aria-hidden="true" size={17} /> Run forecast</Button> : undefined}
        description="Run and review Simple Moving Average demand forecasts for stock products."
        title="Forecasting"
      />
      {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <p className="text-sm text-muted">{runsQuery.data?.meta.total ?? 0} forecast runs {runsQuery.isFetching ? '· Updating…' : ''}</p>
      <ForecastRunTable runs={runsQuery.data?.data ?? []} onView={(run) => setSelectedRunId(run.id)} />

      {isFormOpen ? (
        <ForecastRunFormDialog isSaving={createMutation.isPending} onClose={() => setIsFormOpen(false)} onSave={(payload) => createMutation.mutate(payload)} />
      ) : null}
      {selectedRunQuery.data ? (
        <ForecastRunDetailsDrawer
          canOverride={hasPermission('forecasting.override')}
          isSaving={manualPlanMutation.isPending}
          run={selectedRunQuery.data}
          onClose={() => setSelectedRunId(undefined)}
          onManualPlan={(item, manualQuantity, reason, expiresAt) => manualPlanMutation.mutate({ item, manualQuantity, reason, expiresAt })}
        />
      ) : null}
    </div>
  )
}
