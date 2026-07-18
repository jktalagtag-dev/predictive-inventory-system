import type { ReportRunResult } from '@/features/reports/types/report'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

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
  const [titleColumn, ...detailColumns] = result.columns

  return (
    <div className="space-y-4">
      {aggregateEntries.length > 0 ? (
        <section className="grid gap-3 sm:grid-cols-3">
          {aggregateEntries.map(([key, value]) => (
            <div key={key} className="rounded-xl border border-border bg-surface p-4">
              <p className="text-xs text-muted">{humanizeColumn(key)}</p>
              <p className="mt-1 text-lg font-semibold tabular-nums text-ink">{formatCell(value)}</p>
            </div>
          ))}
        </section>
      ) : null}

      {/* Mobile: card list */}
      <div className="space-y-3 md:hidden">
        {result.rows.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No data for the selected filters.</p>
        ) : (
          result.rows.map((row, index) => (
            <RecordCard
              key={index}
              title={titleColumn ? formatCell(row[titleColumn]) : `Row ${index + 1}`}
              subtitle={titleColumn ? humanizeColumn(titleColumn) : undefined}
              fields={detailColumns.map((column) => ({ label: humanizeColumn(column), value: <span className="tabular-nums">{formatCell(row[column])}</span> }))}
            />
          ))
        )}
      </div>

      {/* Desktop: full table */}
      <div className="hidden md:block">
        <Table minWidth={600}>
          <TableHead>
            <tr>{result.columns.map((column) => <TableHeaderCell key={column}>{humanizeColumn(column)}</TableHeaderCell>)}</tr>
          </TableHead>
          <TableBody>
            {result.rows.length === 0 ? (
              <TableEmptyState colSpan={result.columns.length}>No data for the selected filters.</TableEmptyState>
            ) : (
              result.rows.map((row, index) => (
                <TableRow key={index}>
                  {result.columns.map((column) => <TableCell key={column} className="tabular-nums">{formatCell(row[column])}</TableCell>)}
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      <p className="text-xs text-muted">
        Generated {new Date(result.meta.generatedAt).toLocaleString()} · Timezone {result.meta.timezone} · Currency {result.meta.currency} ·{' '}
        {result.meta.freshness} data · {result.meta.accessClassification} · Showing {result.rows.length} of {result.meta.totalRows} rows
      </p>
    </div>
  )
}
