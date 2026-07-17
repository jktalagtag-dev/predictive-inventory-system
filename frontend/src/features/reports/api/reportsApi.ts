import { apiClient } from '@/shared/api/client'
import type {
  CreateReportExportPayload,
  ReportDefinition,
  ReportExport,
  ReportFilterValues,
  ReportRunResult,
} from '@/features/reports/types/report'

type ApiEnvelope<T> = { data: T }

export const reportQueryKeys = {
  catalog: () => ['reports', 'catalog'] as const,
  run: (code: string, filters: ReportFilterValues) => ['reports', 'run', code, filters] as const,
  exportDetail: (id: string) => ['report-exports', 'detail', id] as const,
}

export async function getReportCatalog(): Promise<ReportDefinition[]> {
  const response = await apiClient.get<ApiEnvelope<ReportDefinition[]>>('/reports')
  return response.data.data
}

export async function runReport(code: string, filters: ReportFilterValues): Promise<ReportRunResult> {
  const response = await apiClient.get<{ data: Omit<ReportRunResult, 'meta'>; meta: ReportRunResult['meta'] }>(`/reports/${code}`, { params: filters })
  return { ...response.data.data, meta: response.data.meta }
}

export async function createReportExport(payload: CreateReportExportPayload): Promise<ReportExport> {
  const response = await apiClient.post<ApiEnvelope<ReportExport>>('/report-exports', payload, {
    headers: { 'Idempotency-Key': crypto.randomUUID() },
  })
  return response.data.data
}

export async function getReportExport(id: string): Promise<ReportExport> {
  const response = await apiClient.get<ApiEnvelope<ReportExport>>(`/report-exports/${id}`)
  return response.data.data
}

export async function downloadReportExport(reportExport: ReportExport): Promise<void> {
  const response = await apiClient.get(`/report-exports/${reportExport.id}/download`, { responseType: 'blob' })
  const url = window.URL.createObjectURL(new Blob([response.data]))
  const link = document.createElement('a')
  link.href = url
  link.download = reportExport.fileName ?? `${reportExport.reportCode}.${reportExport.format}`
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}
