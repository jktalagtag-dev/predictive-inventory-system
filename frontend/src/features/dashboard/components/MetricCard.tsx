import type { ReactNode } from 'react'
import { ArrowUpRight } from 'lucide-react'
import type { DashboardMetric } from '@/features/dashboard/types/dashboard'

type MetricCardProps = {
  metric: DashboardMetric
  icon: ReactNode
  tone: 'default' | 'warning' | 'danger' | 'success'
  isCurrency?: boolean
}

const toneClasses = {
  default: 'bg-brand-50 text-brand-700',
  warning: 'bg-warning/10 text-warning-text',
  danger: 'bg-danger/10 text-danger-text',
  success: 'bg-success/10 text-success-text',
}

function formatMetricValue(value: string, isCurrency: boolean) {
  const numericValue = Number(value)

  // If it's not a plain number string, display the raw value untouched.
  if (Number.isNaN(numericValue)) {
    return value
  }

  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: isCurrency ? 2 : 0,
    maximumFractionDigits: 2,
  }).format(numericValue)
}

export function MetricCard({
  metric,
  icon,
  tone,
  isCurrency = false,
}: MetricCardProps) {
  return (
    <section
      className="
        group
        rounded-2xl
        border
        border-border/60
        bg-surface
        p-7
        shadow-sm
        transition-all
        duration-300
        motion-reduce:transition-none
        hover:-translate-y-1
        hover:border-brand-200
        hover:shadow-xl
      "
    >
      <div className="flex items-start justify-between gap-5">

        <div className="min-w-0 flex-1">

          <p className="text-sm font-semibold tracking-wide text-muted">
            {metric.label}
          </p>

          <h3 className="mt-4 text-5xl font-bold leading-none tracking-tight text-ink tabular-nums">
            {formatMetricValue(metric.value, isCurrency)}
          </h3>

        </div>

        <div
          className={`
            flex
            h-14
            w-14
            shrink-0
            items-center
            justify-center
            rounded-2xl
            transition-transform
            duration-300
            motion-reduce:transition-none
            group-hover:scale-110
            ${toneClasses[tone]}
          `}
        >
          {icon}
        </div>

      </div>

      <div className="mt-6 flex items-center gap-2 text-sm text-muted">

        <ArrowUpRight
          aria-hidden="true"
          size={16}
          className="opacity-70"
        />

        <span className="truncate">
          {metric.detail}
        </span>

      </div>
    </section>
  )
}
