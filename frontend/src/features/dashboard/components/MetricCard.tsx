import type { ReactNode } from 'react'
import { ArrowUpRight } from 'lucide-react'
import type { DashboardMetric } from '@/features/dashboard/types/dashboard'

type MetricCardProps = {
  metric: DashboardMetric
  icon: ReactNode
  tone: 'default' | 'warning' | 'danger' | 'success'
}

const toneClasses = {
  default: 'bg-brand-50 text-brand-700',
  warning: 'bg-warning/10 text-warning-text',
  danger: 'bg-danger/10 text-danger-text',
  success: 'bg-success/10 text-success-text',
}

export function MetricCard({ metric, icon, tone }: MetricCardProps) {
  return (
    <section className="rounded-card border border-border bg-surface p-8 shadow-panel">
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-semibold text-muted">{metric.label}</p>
          <p className="mt-3 text-[2.5rem] font-semibold leading-none tracking-tight text-ink tabular-nums">{metric.value}</p>
        </div>
        <span className={`grid h-10 w-10 place-items-center rounded-xl ${toneClasses[tone]}`}>{icon}</span>
      </div>
      <div className="mt-5 flex items-center gap-1 text-xs leading-5 text-muted">
        <ArrowUpRight aria-hidden="true" size={14} />
        <span>{metric.detail}</span>
      </div>
    </section>
  )
}
