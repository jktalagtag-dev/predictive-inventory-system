import { useState } from 'react'
import { Download, X } from 'lucide-react'
import type { ReportDefinition, ReportExport, ReportFormat } from '@/features/reports/types/report'
import { Button } from '@/shared/components/Button'

type ExportRequestDialogProps = {
  definition: ReportDefinition
  isCreating: boolean
  isDownloading: boolean
  pendingExport: ReportExport | undefined
  onRequestExport: (format: ReportFormat) => void
  onDownload: (reportExport: ReportExport) => void
  onClose: () => void
}

export function ExportRequestDialog({ definition, isCreating, isDownloading, pendingExport, onRequestExport, onDownload, onClose }: ExportRequestDialogProps) {
  const [format, setFormat] = useState<ReportFormat>(definition.permittedFormats[0] ?? 'csv')

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation">
      <section aria-labelledby="export-dialog-title" aria-modal="true" className="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-panel" role="dialog">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h2 id="export-dialog-title" className="text-lg font-bold text-ink">Export {definition.title}</h2>
            <p className="mt-1 text-sm text-muted">Generates the full filtered result set, not only the visible page.</p>
          </div>
          <Button aria-label="Close export dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>

        <div className="mt-6 space-y-4">
          <label className="text-sm font-semibold text-ink">Format
            <select
              className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
              disabled={pendingExport !== undefined}
              value={format}
              onChange={(event) => setFormat(event.target.value as ReportFormat)}
            >
              {definition.permittedFormats.map((option) => <option key={option} value={option}>{option.toUpperCase()}</option>)}
            </select>
          </label>

          {pendingExport ? (
            <div className="rounded-lg border border-border bg-subtle p-4 text-sm">
              <p className="font-medium text-ink">Status: {pendingExport.status}</p>
              {pendingExport.status === 'failed' ? <p className="mt-1 text-red-700">The export could not be generated. Try again.</p> : null}
              {pendingExport.status === 'completed' ? (
                <Button className="mt-3" disabled={isDownloading} onClick={() => onDownload(pendingExport)}>
                  <Download aria-hidden="true" size={16} /> {isDownloading ? 'Downloading…' : 'Download file'}
                </Button>
              ) : (
                <p className="mt-1 text-muted">Generating your export…</p>
              )}
            </div>
          ) : null}

          <div className="flex justify-end gap-3 border-t border-border pt-5">
            <Button type="button" variant="secondary" onClick={onClose}>Close</Button>
            {pendingExport === undefined ? (
              <Button disabled={isCreating} onClick={() => onRequestExport(format)}>{isCreating ? 'Requesting…' : 'Request export'}</Button>
            ) : null}
          </div>
        </div>
      </section>
    </div>
  )
}
