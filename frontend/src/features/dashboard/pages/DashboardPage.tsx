import { AlertCircle, Boxes, CircleAlert, PackageCheck, RefreshCw, TrendingUp } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthProvider'
import { useDashboard } from '@/features/dashboard/hooks/useDashboard'
import { ForecastSummaryPanel } from '@/features/dashboard/components/ForecastSummaryPanel'
import { LowStockTable } from '@/features/dashboard/components/LowStockTable'
import { MetricCard } from '@/features/dashboard/components/MetricCard'
import { PendingPurchaseOrdersPanel } from '@/features/dashboard/components/PendingPurchaseOrdersPanel'
import { RecentSalesPanel } from '@/features/dashboard/components/RecentSalesPanel'
import { SalesTrendTable } from '@/features/dashboard/components/SalesTrendTable'
import { SyncHealthPanel } from '@/features/dashboard/components/SyncHealthPanel'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

export default function DashboardPage() {
  const { session } = useAuth()
  const branchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id
  const dashboardQuery = useDashboard(branchId)
  const error = dashboardQuery.error as ApiError | null

  return (
    <div className="space-y-6">
      <PageHeader
        title="Dashboard"
        description="Operational overview for inventory, sales, and replenishment decisions."
        actions={(
          <Button disabled={dashboardQuery.isFetching} variant="secondary" onClick={() => void dashboardQuery.refetch()}>
            <RefreshCw aria-hidden="true" className={dashboardQuery.isFetching ? 'animate-spin' : undefined} size={16} />
            Refresh
          </Button>
        )}
      />

      {!branchId ? (
        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
          You are not assigned to a branch, so no dashboard data is available.
        </div>
      ) : dashboardQuery.isLoading ? (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" role="status">
          {[1, 2, 3, 4].map((key) => (
            <div key={key} className="h-32 animate-pulse rounded-xl border border-border bg-subtle" />
          ))}
        </div>
      ) : dashboardQuery.isError && error ? (
        <div className="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
          <AlertCircle aria-hidden="true" size={16} />
          {error.message}
          {error.requestId ? ` Request ID: ${error.requestId}` : ''}
        </div>
      ) : dashboardQuery.data ? (
        <>
          <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Live data refreshed {new Date(dashboardQuery.data.meta.generatedAt).toLocaleString()} · Scope: {dashboardQuery.data.meta.from} to{' '}
            {dashboardQuery.data.meta.to} ({dashboardQuery.data.meta.timezone}) · Currency: {dashboardQuery.data.meta.currency}
          </div>

          <section aria-label="Operational summary" className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard icon={<Boxes aria-hidden="true" size={20} />} metric={dashboardQuery.data.data.kpis.inventoryOnHand} tone="default" />
            <MetricCard icon={<TrendingUp aria-hidden="true" size={20} />} metric={dashboardQuery.data.data.kpis.salesToday} tone="success" />
            <MetricCard icon={<PackageCheck aria-hidden="true" size={20} />} metric={dashboardQuery.data.data.kpis.lowStockCount} tone="warning" />
            <MetricCard icon={<CircleAlert aria-hidden="true" size={20} />} metric={dashboardQuery.data.data.kpis.criticalStockCount} tone="danger" />
          </section>

          <section className="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)]">
            <LowStockTable items={dashboardQuery.data.data.lowStock} />
            <RecentSalesPanel sales={dashboardQuery.data.data.recentSales} />
          </section>

          <section className="grid gap-6 lg:grid-cols-2">
            <PendingPurchaseOrdersPanel count={dashboardQuery.data.data.pendingPurchaseOrders.count} items={dashboardQuery.data.data.pendingPurchaseOrders.items} />
            <ForecastSummaryPanel summary={dashboardQuery.data.data.forecastSummary} />
          </section>

          <section className="grid gap-6 lg:grid-cols-2">
            <SalesTrendTable points={dashboardQuery.data.data.salesTrend} />
            <SyncHealthPanel health={dashboardQuery.data.data.syncHealth} />
          </section>
        </>
      ) : null}
    </div>
  )
}
