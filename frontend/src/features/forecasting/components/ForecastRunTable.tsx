import { PanelRightOpen } from 'lucide-react'
import type { ForecastRun } from '@/features/forecasting/types/forecast'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

export function ForecastRunTable({ runs, onView }: { runs: ForecastRun[]; onView: (run: ForecastRun) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {runs.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No forecast runs yet.</p>
        ) : (
          runs.map((run) => (
            <RecordCard
              key={run.id}
              ariaLabel={`View run ${run.id}`}
              title={<span className="capitalize">{run.periodGrain} · {run.windowPeriods} periods</span>}
              subtitle={<span className="font-mono">#{run.id} · {run.createdAt ? new Date(run.createdAt).toLocaleString() : '—'}</span>}
              fields={[
                { label: 'History range', value: `${run.historyStartDate} – ${run.historyEndDate}`, full: true },
                { label: 'Products', value: run.itemCount ?? '—' },
              ]}
              onClick={() => onView(run)}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={700}>
          <TableHead>
            <tr>
              <TableHeaderCell>Run</TableHeaderCell>
              <TableHeaderCell>Period grain</TableHeaderCell>
              <TableHeaderCell>Window</TableHeaderCell>
              <TableHeaderCell>History range</TableHeaderCell>
              <TableHeaderCell align="right">Products</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {runs.length === 0 ? (
              <TableEmptyState colSpan={6}>No forecast runs yet.</TableEmptyState>
            ) : (
              runs.map((run) => (
                <TableRow key={run.id}>
                  <TableCell className="font-mono text-xs text-muted">#{run.id} · {run.createdAt ? new Date(run.createdAt).toLocaleString() : '—'}</TableCell>
                  <TableCell className="capitalize text-ink">{run.periodGrain}</TableCell>
                  <TableCell className="text-ink">{run.windowPeriods} periods</TableCell>
                  <TableCell className="text-muted">{run.historyStartDate} – {run.historyEndDate}</TableCell>
                  <TableCell align="right" className="text-ink">{run.itemCount ?? '—'}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`View run ${run.id}`} size="icon" variant="ghost" onClick={() => onView(run)}>
                        <PanelRightOpen aria-hidden="true" size={18} />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </>
  )
}
