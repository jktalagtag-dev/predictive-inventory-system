import { PanelRightOpen } from 'lucide-react'
import type { ForecastRun } from '@/features/forecasting/types/forecast'
import { Button } from '@/shared/components/Button'

export function ForecastRunTable({ runs, onView }: { runs: ForecastRun[]; onView: (run: ForecastRun) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[700px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr><th className="px-5 py-3">Run</th><th className="px-5 py-3">Period grain</th><th className="px-5 py-3">Window</th><th className="px-5 py-3">History range</th><th className="px-5 py-3 text-right">Products</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {runs.map((run) => (
              <tr key={run.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4 font-mono text-xs text-muted">#{run.id} · {run.createdAt ? new Date(run.createdAt).toLocaleString() : '—'}</td>
                <td className="px-5 py-4 capitalize text-ink">{run.periodGrain}</td>
                <td className="px-5 py-4 text-ink">{run.windowPeriods} periods</td>
                <td className="px-5 py-4 text-muted">{run.historyStartDate} – {run.historyEndDate}</td>
                <td className="px-5 py-4 text-right tabular-nums text-ink">{run.itemCount ?? '—'}</td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`View run ${run.id}`} size="icon" variant="ghost" onClick={() => onView(run)}><PanelRightOpen aria-hidden="true" size={17} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
