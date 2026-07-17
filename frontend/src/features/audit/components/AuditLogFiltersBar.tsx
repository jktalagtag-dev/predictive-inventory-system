import type { FormEvent } from 'react'
import type { AuditLogFilters } from '@/features/audit/types/audit'
import { Button } from '@/shared/components/Button'

type BranchOption = { id: string; name: string }

type AuditLogFiltersBarProps = {
  filters: AuditLogFilters
  branchOptions: BranchOption[]
  onChange: (filters: AuditLogFilters) => void
}

const inputClass = 'h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20'

export function AuditLogFiltersBar({ filters, branchOptions, onChange }: AuditLogFiltersBarProps) {
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onChange({ ...filters, page: 1 })
  }

  return (
    <form className="grid gap-3 rounded-xl border border-border bg-surface p-4 shadow-panel sm:grid-cols-3 lg:grid-cols-6" onSubmit={submit}>
      <label className="text-xs font-semibold text-muted">Branch
        <select className={inputClass} value={filters.branchId ?? ''} onChange={(event) => onChange({ ...filters, branchId: event.target.value || null, page: 1 })}>
          <option value="">All branches</option>
          {branchOptions.map((branch) => <option key={branch.id} value={branch.id}>{branch.name}</option>)}
        </select>
      </label>
      <label className="text-xs font-semibold text-muted">Event type
        <input className={inputClass} placeholder="e.g. sale.voided" value={filters.eventType ?? ''} onChange={(event) => onChange({ ...filters, eventType: event.target.value, page: 1 })} />
      </label>
      <label className="text-xs font-semibold text-muted">Entity type
        <input className={inputClass} placeholder="e.g. purchase_order" value={filters.entityType ?? ''} onChange={(event) => onChange({ ...filters, entityType: event.target.value, page: 1 })} />
      </label>
      <label className="text-xs font-semibold text-muted">Entity ID
        <input className={inputClass} value={filters.entityId ?? ''} onChange={(event) => onChange({ ...filters, entityId: event.target.value, page: 1 })} />
      </label>
      <label className="text-xs font-semibold text-muted">From
        <input className={inputClass} type="date" value={filters.from ?? ''} onChange={(event) => onChange({ ...filters, from: event.target.value, page: 1 })} />
      </label>
      <label className="text-xs font-semibold text-muted">To
        <input className={inputClass} type="date" value={filters.to ?? ''} onChange={(event) => onChange({ ...filters, to: event.target.value, page: 1 })} />
      </label>
      <div className="sm:col-span-3 lg:col-span-6"><Button type="submit">Search</Button></div>
    </form>
  )
}
