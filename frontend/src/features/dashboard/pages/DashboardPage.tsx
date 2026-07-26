import {
  AlertCircle,
  Boxes,
  CircleAlert,
  PackageCheck,
  RefreshCw,
  TrendingUp,
} from 'lucide-react'

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

export default function DashboardPage() {
  const { session } = useAuth()

  const branchId =
    (
      session?.user.branches.find((branch) => branch.isDefault) ??
      session?.user.branches[0]
    )?.id

  const dashboardQuery = useDashboard(branchId)

  const error = dashboardQuery.error as ApiError | null

  return (
    <div className="space-y-5">

      {/* Compact Header */}
      <section className="flex flex-col gap-3 border-b border-border pb-5 md:flex-row md:items-center md:justify-between">

        <div className="min-w-0">

          <h1 className="text-2xl font-bold tracking-tight text-ink sm:text-3xl">
            Dashboard
          </h1>

          {dashboardQuery.data && (
            <p className="mt-2 text-xs text-muted">
              Updated{' '}
              {new Date(
                dashboardQuery.data.meta.generatedAt,
              ).toLocaleString()}
              {' • '}
              {dashboardQuery.data.meta.from}
              {' – '}
              {dashboardQuery.data.meta.to}
              {' • '}
              {dashboardQuery.data.meta.currency}
            </p>
          )}

        </div>

        <Button
          aria-label="Refresh dashboard"
          size="icon"
          variant="secondary"
          disabled={dashboardQuery.isFetching}
          onClick={() => void dashboardQuery.refetch()}
        >
          <RefreshCw
            aria-hidden="true"
            size={16}
            className={
              dashboardQuery.isFetching
                ? 'animate-spin'
                : undefined
            }
          />
        </Button>

      </section>

      {!branchId ? (
        <div
          className="rounded-xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-warning-text"
          role="status"
        >
          You are not assigned to a branch, so no dashboard data is available.
        </div>
      ) : dashboardQuery.isLoading ? (
        <div
          className="grid gap-6 sm:grid-cols-2 xl:grid-cols-4"
          role="status"
        >
          {[1, 2, 3, 4].map((key) => (
            <div
              key={key}
              className="h-36 animate-pulse rounded-card border border-border bg-subtle"
            />
          ))}
        </div>
      ) : dashboardQuery.isError && error ? (
        <div
          className="flex items-center gap-2 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text"
          role="alert"
        >
          <AlertCircle aria-hidden="true" size={16} />
          {error.message}
          {error.requestId
            ? ` Request ID: ${error.requestId}`
            : ''}
        </div>
      ) : dashboardQuery.data ? (
        <>

          {/* KPI Cards */}

          <section
            aria-label="Operational summary"
            className="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4"
          >
            <MetricCard
              icon={<Boxes aria-hidden="true" size={22} />}
              metric={dashboardQuery.data.data.kpis.inventoryOnHand}
              tone="default"
            />

            <MetricCard
              icon={<TrendingUp aria-hidden="true" size={22} />}
              isCurrency
              metric={dashboardQuery.data.data.kpis.salesToday}
              tone="success"
            />

            <MetricCard
              icon={<PackageCheck aria-hidden="true" size={22} />}
              metric={dashboardQuery.data.data.kpis.lowStockCount}
              tone="warning"
            />

            <MetricCard
              icon={<CircleAlert aria-hidden="true" size={22} />}
              metric={dashboardQuery.data.data.kpis.criticalStockCount}
              tone="danger"
            />
          </section>

          {/* Main Row */}

          <section className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)]">

            <LowStockTable
              items={dashboardQuery.data.data.lowStock}
            />

            <RecentSalesPanel
              sales={dashboardQuery.data.data.recentSales}
            />

          </section>

          {/* Secondary */}

          <section className="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <PendingPurchaseOrdersPanel
              count={
                dashboardQuery.data.data.pendingPurchaseOrders.count
              }
              items={
                dashboardQuery.data.data.pendingPurchaseOrders.items
              }
            />

            <ForecastSummaryPanel
              summary={
                dashboardQuery.data.data.forecastSummary
              }
            />

          </section>

          {/* Bottom */}

          <section className="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <SalesTrendTable
              points={dashboardQuery.data.data.salesTrend}
            />

            <SyncHealthPanel
              health={dashboardQuery.data.data.syncHealth}
            />

          </section>

        </>
      ) : null}
    </div>
  )
}
