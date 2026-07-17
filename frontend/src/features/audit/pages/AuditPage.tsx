import { useState } from 'react'
import { useAuth } from '@/features/auth/AuthProvider'
import { AuditLogDetailsDrawer } from '@/features/audit/components/AuditLogDetailsDrawer'
import { AuditLogFiltersBar } from '@/features/audit/components/AuditLogFiltersBar'
import { AuditLogTable } from '@/features/audit/components/AuditLogTable'
import { useAuditLog, useAuditLogs } from '@/features/audit/hooks/useAudit'
import type { AuditLogFilters } from '@/features/audit/types/audit'
import { type ApiError } from '@/shared/api/client'
import { PageHeader } from '@/shared/components/PageHeader'

const defaultFilters: AuditLogFilters = { branchId: null, page: 1, perPage: 20 }

export default function AuditPage() {
  const { session } = useAuth()
  const [filters, setFilters] = useState<AuditLogFilters>(defaultFilters)
  const [selectedId, setSelectedId] = useState<string | undefined>()

  const auditQuery = useAuditLogs(filters)
  const selectedEntryQuery = useAuditLog(selectedId)

  const branchOptions = session?.user.branches.map((branch) => ({ id: branch.id, name: branch.name })) ?? []
  const error = auditQuery.error as ApiError | null

  return (
    <div className="space-y-6">
      <PageHeader description="Search the append-only trail of authenticated actions, approvals, and inventory-affecting events." title="Audit trail" />
      {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <AuditLogFiltersBar branchOptions={branchOptions} filters={filters} onChange={setFilters} />

      <p className="text-sm text-muted">{auditQuery.data?.meta.total ?? 0} events {auditQuery.isFetching ? '· Updating…' : ''}</p>
      <AuditLogTable entries={auditQuery.data?.data ?? []} onView={(entry) => setSelectedId(entry.id)} />

      {selectedEntryQuery.data ? <AuditLogDetailsDrawer entry={selectedEntryQuery.data} onClose={() => setSelectedId(undefined)} /> : null}
    </div>
  )
}
