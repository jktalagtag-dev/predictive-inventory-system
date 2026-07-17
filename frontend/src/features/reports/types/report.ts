export type ReportFormat = 'pdf' | 'csv' | 'xlsx'

export type ReportFilterSpec = {
  key: string
  type: 'integer' | 'string' | 'boolean' | 'date'
  required: boolean
}

export type ReportDefinition = {
  code: string
  title: string
  description: string
  permittedFormats: ReportFormat[]
  filters: ReportFilterSpec[]
  columns: string[]
  dataClassification: string
  requiredPermission: string
}

export type ReportRunMeta = {
  reportCode: string
  filterSummary: Record<string, unknown>
  timezone: string
  currency: string
  generatedAt: string
  dataCutoffAt: string
  freshness: string
  accessClassification: string
  page: number
  perPage: number
  totalRows: number
}

export type ReportRunResult = {
  columns: string[]
  rows: Array<Record<string, unknown>>
  aggregates: Record<string, unknown>
  meta: ReportRunMeta
}

export type ReportFilterValues = Record<string, string | number | boolean | undefined>

export type ReportExportStatus = 'queued' | 'running' | 'completed' | 'failed' | 'expired'

export type ReportExport = {
  id: string
  reportCode: string
  format: ReportFormat
  status: ReportExportStatus
  branchId: string | null
  filtersSnapshot: Record<string, unknown>
  dataCutoffAt: string | null
  fileName: string | null
  fileSizeBytes: number | null
  requestedAt: string | null
  completedAt: string | null
  expiresAt: string | null
  failureCode: string | null
  downloadLink: string | null
}

export type CreateReportExportPayload = {
  reportCode: string
  format: ReportFormat
  branchId?: string
  filters: ReportFilterValues
}
