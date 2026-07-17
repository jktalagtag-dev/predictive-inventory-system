import { Sparkles } from 'lucide-react'
import { Link } from 'react-router-dom'
import type { ForecastSummary } from '@/features/dashboard/types/dashboard'

export function ForecastSummaryPanel({ summary }: { summary: ForecastSummary }) {
  return (
    <section className="rounded-xl border border-border bg-surface p-5 shadow-panel">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h2 className="text-base font-semibold text-ink">Forecast coverage</h2>
          <p className="mt-1 text-sm text-muted">Latest completed forecast run for this branch.</p>
        </div>
        <Link className="text-sm font-medium text-brand-700 hover:underline" to="/forecasting">View all</Link>
      </div>
      {summary === null ? (
        <p className="mt-4 text-sm text-muted">No completed forecast run yet. Planning is a manual step until one is run.</p>
      ) : (
        <div className="mt-4 flex items-start gap-3">
          <span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
            <Sparkles aria-hidden="true" size={17} />
          </span>
          <div className="min-w-0 flex-1">
            <p className="text-2xl font-bold tabular-nums text-ink">{Math.round(summary.coverageRatio * 100)}%</p>
            <p className="mt-1 text-sm text-muted">
              {summary.sufficientHistoryCount} of {summary.totalProductCount} products have sufficient history ·{' '}
              {summary.periodGrain} {summary.modelCode.toUpperCase()}
            </p>
            <p className="mt-1 text-xs text-muted">{summary.generatedAt ? new Date(summary.generatedAt).toLocaleString() : '—'}</p>
          </div>
        </div>
      )}
    </section>
  )
}
