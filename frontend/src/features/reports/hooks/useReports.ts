import { useQuery } from '@tanstack/react-query'
import { getReportCatalog, getReportExport, reportQueryKeys, runReport } from '@/features/reports/api/reportsApi'
import type { ReportFilterValues } from '@/features/reports/types/report'

export function useReportCatalog() {
  return useQuery({ queryKey: reportQueryKeys.catalog(), queryFn: getReportCatalog, staleTime: 5 * 60 * 1000 })
}

export function useReportRun(code: string | undefined, filters: ReportFilterValues, enabled: boolean) {
  return useQuery({
    queryKey: reportQueryKeys.run(code ?? '', filters),
    queryFn: () => runReport(code as string, filters),
    enabled: enabled && code !== undefined,
  })
}

export function useReportExportStatus(id: string | undefined) {
  return useQuery({
    queryKey: reportQueryKeys.exportDetail(id ?? ''),
    queryFn: () => getReportExport(id as string),
    enabled: id !== undefined,
    refetchInterval: (query) => (query.state.data && ['queued', 'running'].includes(query.state.data.status) ? 1500 : false),
  })
}
