import { Activity, Boxes, CircleAlert, ClipboardList, PackageCheck, RefreshCw, TrendingUp } from 'lucide-react'
import { useDashboard } from '@/features/dashboard/hooks/useDashboard'
import { MetricCard } from '@/features/dashboard/components/MetricCard'
import { RecentActivity } from '@/features/dashboard/components/RecentActivity'
import { StockAlertTable } from '@/features/dashboard/components/StockAlertTable'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

export default function DashboardPage() {
  const dashboardQuery = useDashboard()
  const dashboard = dashboardQuery.data

  if (!dashboard) {
    return null
  }

  const generatedAt = new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Asia/Manila',
  }).format(new Date(dashboard.generatedAt))

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

      <div className={`rounded-lg border px-4 py-3 text-sm ${dashboardQuery.isPlaceholderData
        ? 'border-amber-200 bg-amber-50 text-amber-800'
        : 'border-emerald-200 bg-emerald-50 text-emerald-800'}`}
      >
        {dashboardQuery.isPlaceholderData
          ? 'Preview data is shown until the dashboard API is available.'
          : `Live data refreshed ${generatedAt} (Asia/Manila).`}
      </div>

      <section aria-label="Operational summary" className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <MetricCard icon={<Boxes aria-hidden="true" size={20} />} metric={dashboard.inventorySummary} tone="default" />
        <MetricCard icon={<TrendingUp aria-hidden="true" size={20} />} metric={dashboard.salesToday} tone="success" />
        <MetricCard icon={<PackageCheck aria-hidden="true" size={20} />} metric={dashboard.lowStock} tone="warning" />
        <MetricCard icon={<CircleAlert aria-hidden="true" size={20} />} metric={dashboard.criticalStock} tone="danger" />
        <MetricCard icon={<Activity aria-hidden="true" size={20} />} metric={dashboard.forecastSummary} tone="default" />
        <MetricCard icon={<ClipboardList aria-hidden="true" size={20} />} metric={dashboard.eoqSummary} tone="default" />
      </section>

      <section className="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)]">
        <StockAlertTable alerts={dashboard.stockAlerts} />
        <RecentActivity items={dashboard.recentActivity} />
      </section>

      <footer className="text-xs text-muted">
        Scope: authorized branch context · Currency: Philippine Peso · Generated: {generatedAt}
      </footer>
    </div>
  )
}
