import { PanelRightOpen } from 'lucide-react'
import type { AuditLogEntry } from '@/features/audit/types/audit'
import { Button } from '@/shared/components/Button'

export function AuditLogTable({ entries, onView }: { entries: AuditLogEntry[]; onView: (entry: AuditLogEntry) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[800px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr>
              <th className="px-4 py-3">When</th>
              <th className="px-4 py-3">Action</th>
              <th className="px-4 py-3">Entity</th>
              <th className="px-4 py-3">Actor role</th>
              <th className="px-4 py-3">Correlation ID</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {entries.length === 0 ? (
              <tr><td className="px-4 py-6 text-center text-sm text-muted" colSpan={6}>No audit events match these filters.</td></tr>
            ) : entries.map((entry) => (
              <tr key={entry.id} className="hover:bg-subtle/70">
                <td className="px-4 py-3 whitespace-nowrap tabular-nums">{entry.createdAt ? new Date(entry.createdAt).toLocaleString() : '—'}</td>
                <td className="px-4 py-3 font-medium text-ink">{entry.action}</td>
                <td className="px-4 py-3">{entry.entityType}{entry.entityId ? ` #${entry.entityId}` : ''}</td>
                <td className="px-4 py-3">{entry.actorRole ?? '—'}</td>
                <td className="px-4 py-3 font-mono text-xs text-muted">{entry.correlationId}</td>
                <td className="px-4 py-3"><div className="flex justify-end"><Button aria-label="View audit event details" size="icon" variant="ghost" onClick={() => onView(entry)}><PanelRightOpen aria-hidden="true" size={17} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
