import { apiClient } from '@/shared/api/client'
import type { AuditLogEntry, AuditLogFilters, PaginatedAuditLogs } from '@/features/audit/types/audit'

type ApiEnvelope<T> = { data: T; meta?: PaginatedAuditLogs['meta'] }

export const auditQueryKeys = {
  lists: () => ['audit-logs'] as const,
  list: (filters: AuditLogFilters) => ['audit-logs', filters] as const,
  detail: (id: string) => ['audit-logs', 'detail', id] as const,
}

export async function getAuditLogs(filters: AuditLogFilters): Promise<PaginatedAuditLogs> {
  const response = await apiClient.get<ApiEnvelope<AuditLogEntry[]>>('/audit-logs', {
    params: {
      branchId: filters.branchId ?? undefined,
      actorUserId: filters.actorUserId || undefined,
      eventType: filters.eventType || undefined,
      entityType: filters.entityType || undefined,
      entityId: filters.entityId || undefined,
      correlationId: filters.correlationId || undefined,
      from: filters.from || undefined,
      to: filters.to || undefined,
      page: filters.page,
      perPage: filters.perPage,
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function getAuditLog(id: string): Promise<AuditLogEntry> {
  const response = await apiClient.get<ApiEnvelope<AuditLogEntry>>(`/audit-logs/${id}`)
  return response.data.data
}
