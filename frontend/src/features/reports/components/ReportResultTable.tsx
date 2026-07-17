import type { ReportRunResult } from '@/features/reports/types/report'

function humanizeColumn(column: string): string {
  const withSpaces = column.replace(/([A-Z])/g, ' $1')
  return withSpaces.charAt(0).toUpperCase() + withSpaces.slice(1)
}

function formatCell(value: unknown): string {
  if (value === null || value === undefined) return '—'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  return String(value)
}

export function ReportResultTable({ result }: { result: ReportRunResult }) {
  const aggregateEntries = Object.entries(result.aggregates).filter(([, value]) => typeof value !== 'object')

  return (
    <div className="space-y-4">
      {aggregateEntries.length > 0 ? (
        <section className="grid gap-3 sm:grid-cols-3">
          {aggregateEntries.map(([key, value]) => (
            <div key={key} className="rounded-lg border border-border bg-surface p-3">
              <p className="text-xs text-muted">{humanizeColumn(key)}</p>
              <p className="mt-1 text-lg font-semibold tabular-nums text-ink">{formatCell(value)}</p>
            </div>
          ))}
        </section>
      ) : null}

      <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[600px] text-sm">
            <thead className="bg-subtle text-left text-xs font-semibold text-muted">
              <tr>{result.columns.map((column) => <th key={column} className="px-4 py-3">{humanizeColumn(column)}</th>)}</tr>
            </thead>
            <tbody className="divide-y divide-border">
              {result.rows.length === 0 ? (
                <tr><td className="px-4 py-6 text-center text-sm text-muted" colSpan={result.columns.length}>No data for the selected filters.</td></tr>
              ) : result.rows.map((row, index) => (
                <tr key={index} className="hover:bg-subtle/70">
                  {result.columns.map((column) => <td key={column} className="px-4 py-3 tabular-nums">{formatCell(row[column])}</td>)}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <p className="text-xs text-muted">
        Generated {new Date(result.meta.generatedAt).toLocaleString()} · Timezone {result.meta.timezone} · Currency {result.meta.currency} ·{' '}
        {result.meta.freshness} data · {result.meta.accessClassification} · Showing {result.rows.length} of {result.meta.totalRows} rows
      </p>
    </div>
  )
}
