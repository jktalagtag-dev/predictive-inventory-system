import type { ReportDefinition } from '@/features/reports/types/report'
import { cn } from '@/shared/lib/cn'

type ReportCatalogSidebarProps = {
  reports: ReportDefinition[]
  selectedCode: string | undefined
  onSelect: (code: string) => void
}

export function ReportCatalogSidebar({ reports, selectedCode, onSelect }: ReportCatalogSidebarProps) {
  if (reports.length === 0) {
    return <p className="text-sm text-muted">No reports are available for your role.</p>
  }

  return (
    <nav aria-label="Report catalog" className="space-y-1.5">
      {reports.map((report) => (
        <button
          key={report.code}
          aria-current={selectedCode === report.code ? 'true' : undefined}
          className={cn(
            'block w-full rounded-lg border px-4 py-3 text-left transition-colors',
            selectedCode === report.code ? 'border-brand-600 bg-brand-50' : 'border-border bg-surface hover:bg-subtle',
          )}
          type="button"
          onClick={() => onSelect(report.code)}
        >
          <p className="text-sm font-semibold text-ink">{report.title}</p>
          <p className="mt-0.5 text-xs text-muted">{report.description}</p>
        </button>
      ))}
    </nav>
  )
}
