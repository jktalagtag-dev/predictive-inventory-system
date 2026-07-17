import { useEffect, useState } from 'react'
import { Download } from 'lucide-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/features/auth/AuthProvider'
import { createReportExport, downloadReportExport, reportQueryKeys } from '@/features/reports/api/reportsApi'
import { ExportRequestDialog } from '@/features/reports/components/ExportRequestDialog'
import { ReportCatalogSidebar } from '@/features/reports/components/ReportCatalogSidebar'
import { ReportFilterForm } from '@/features/reports/components/ReportFilterForm'
import { ReportResultTable } from '@/features/reports/components/ReportResultTable'
import { useReportCatalog, useReportExportStatus, useReportRun } from '@/features/reports/hooks/useReports'
import type { ReportFilterValues, ReportFormat } from '@/features/reports/types/report'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

export default function ReportsPage() {
  const { session, hasPermission } = useAuth()
  const catalogQuery = useReportCatalog()
  const queryClient = useQueryClient()

  const [selectedCode, setSelectedCode] = useState<string | undefined>()
  const [filterValues, setFilterValues] = useState<ReportFilterValues>({})
  const [hasRun, setHasRun] = useState(false)
  const [isExportOpen, setIsExportOpen] = useState(false)
  const [exportId, setExportId] = useState<string | undefined>()

  const definition = catalogQuery.data?.find((report) => report.code === selectedCode)
  const branchOptions = session?.user.branches.map((branch) => ({ id: branch.id, name: branch.name })) ?? []

  useEffect(() => {
    if (!catalogQuery.data || selectedCode) return
    const first = catalogQuery.data[0]
    if (first) setSelectedCode(first.code)
  }, [catalogQuery.data, selectedCode])

  useEffect(() => {
    setHasRun(false)
    setExportId(undefined)
    const defaultBranchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id
    setFilterValues(definition?.filters.some((filter) => filter.key === 'branchId') ? { branchId: defaultBranchId } : {})
  }, [selectedCode, definition, session])

  const requiredFiltersMet = definition ? definition.filters.filter((filter) => filter.required).every((filter) => filterValues[filter.key] !== undefined && filterValues[filter.key] !== '') : false

  const reportRunQuery = useReportRun(selectedCode, filterValues, hasRun && requiredFiltersMet)
  const exportStatusQuery = useReportExportStatus(exportId)

  const createExportMutation = useMutation({
    mutationFn: (format: ReportFormat) => createReportExport({ reportCode: selectedCode as string, format, branchId: filterValues.branchId as string | undefined, filters: filterValues }),
    onSuccess: (created) => {
      setExportId(created.id)
      void queryClient.invalidateQueries({ queryKey: reportQueryKeys.exportDetail(created.id) })
    },
  })
  const downloadMutation = useMutation({ mutationFn: downloadReportExport })

  const error = (createExportMutation.error ?? reportRunQuery.error) as ApiError | null

  return (
    <div className="space-y-6">
      <PageHeader description="Run governed operational reports and request exports of the full filtered result set." title="Reports" />
      {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <div className="grid gap-6 lg:grid-cols-[280px_1fr]">
        <ReportCatalogSidebar reports={catalogQuery.data ?? []} selectedCode={selectedCode} onSelect={setSelectedCode} />

        {definition ? (
          <div className="space-y-4">
            <ReportFilterForm
              branchOptions={branchOptions}
              definition={definition}
              isRunning={reportRunQuery.isFetching}
              values={filterValues}
              onChange={(key, value) => setFilterValues((state) => ({ ...state, [key]: value }))}
              onRun={() => setHasRun(true)}
            />

            {hasPermission('reports.export') ? (
              <div className="flex justify-end">
                <Button disabled={!requiredFiltersMet} variant="secondary" onClick={() => setIsExportOpen(true)}>
                  <Download aria-hidden="true" size={16} /> Export
                </Button>
              </div>
            ) : null}

            {reportRunQuery.data ? <ReportResultTable result={reportRunQuery.data} /> : hasRun ? <p className="text-sm text-muted">{reportRunQuery.isFetching ? 'Running report…' : 'No results.'}</p> : <p className="text-sm text-muted">Set the required filters and run the report.</p>}
          </div>
        ) : <p className="text-sm text-muted">{catalogQuery.isLoading ? 'Loading report catalog…' : 'Select a report to begin.'}</p>}
      </div>

      {isExportOpen && definition ? (
        <ExportRequestDialog
          definition={definition}
          isCreating={createExportMutation.isPending}
          isDownloading={downloadMutation.isPending}
          pendingExport={exportStatusQuery.data}
          onClose={() => { setIsExportOpen(false); setExportId(undefined) }}
          onDownload={(reportExport) => downloadMutation.mutate(reportExport)}
          onRequestExport={(format) => createExportMutation.mutate(format)}
        />
      ) : null}
    </div>
  )
}
