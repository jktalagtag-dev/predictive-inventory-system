import { useQuery } from '@tanstack/react-query'
import { auditQueryKeys, getAuditLog, getAuditLogs } from '@/features/audit/api/auditApi'
import type { AuditLogFilters } from '@/features/audit/types/audit'

export function useAuditLogs(filters: AuditLogFilters) {
  return useQuery({ queryKey: auditQueryKeys.list(filters), queryFn: () => getAuditLogs(filters) })
}

export function useAuditLog(id: string | undefined) {
  return useQuery({ queryKey: auditQueryKeys.detail(id ?? ''), queryFn: () => getAuditLog(id as string), enabled: id !== undefined })
}
