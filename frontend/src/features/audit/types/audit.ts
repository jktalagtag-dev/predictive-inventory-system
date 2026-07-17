export type AuditLogEntry = {
  id: string
  actorUserId: string | null
  actorRole: string | null
  action: string
  entityType: string
  entityId: string | null
  branchId: string | null
  correlationId: string
  schemaVersion: number
  changes: { before?: Record<string, unknown>; after?: Record<string, unknown> } | null
  createdAt: string | null
}

export type AuditLogFilters = {
  branchId: string | null
  actorUserId?: string
  eventType?: string
  entityType?: string
  entityId?: string
  correlationId?: string
  from?: string
  to?: string
  page: number
  perPage: number
}

export type PaginatedAuditLogs = {
  data: AuditLogEntry[]
  meta: { page: number; perPage: number; total: number }
}
