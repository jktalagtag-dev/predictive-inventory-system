import { X } from 'lucide-react'
import type { AuditLogEntry } from '@/features/audit/types/audit'
import { Button } from '@/shared/components/Button'
import { drawerOverlayClass, drawerPanelClass } from '@/shared/lib/modalClasses'
import { Portal } from '@/shared/components/Portal'

export function AuditLogDetailsDrawer({ entry, onClose }: { entry: AuditLogEntry; onClose: () => void }) {
  return (
    <Portal>
    <div className={drawerOverlayClass} role="presentation" onMouseDown={onClose}>
      <aside aria-labelledby="audit-details-title" aria-modal="true" className={drawerPanelClass('sm:max-w-xl')} role="dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="flex items-start justify-between gap-4 border-b border-border p-4 sm:p-6">
          <div>
            <p className="font-mono text-xs text-muted">Audit event #{entry.id}</p>
            <h2 id="audit-details-title" className="mt-1 text-xl font-bold tracking-tight text-ink">{entry.action}</h2>
            <p className="mt-1 text-sm text-muted">{entry.entityType}{entry.entityId ? ` #${entry.entityId}` : ''}</p>
          </div>
          <Button aria-label="Close audit event details" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </header>
        <div className="flex-1 space-y-6 overflow-y-auto p-4 sm:p-6">
          <section className="grid grid-cols-1 gap-4 rounded-xl border border-border p-4 text-sm sm:grid-cols-2">
            <div><p className="text-muted">When</p><p className="font-semibold text-ink">{entry.createdAt ? new Date(entry.createdAt).toLocaleString() : '—'}</p></div>
            <div><p className="text-muted">Actor role</p><p className="font-semibold text-ink">{entry.actorRole ?? '—'}</p></div>
            <div><p className="text-muted">Branch</p><p className="font-semibold text-ink">{entry.branchId ?? 'Global'}</p></div>
            <div><p className="text-muted">Schema version</p><p className="font-semibold text-ink">{entry.schemaVersion}</p></div>
            <div className="col-span-1 sm:col-span-2"><p className="text-muted">Correlation ID</p><p className="font-mono text-xs font-semibold text-ink">{entry.correlationId}</p></div>
          </section>

          {entry.changes ? (
            <section className="space-y-3">
              <h3 className="text-sm font-semibold text-ink">Redacted change record</h3>
              {entry.changes.before ? (
                <div>
                  <p className="text-xs font-semibold text-muted">Before</p>
                  <pre className="mt-1 overflow-x-auto rounded-xl border border-border bg-subtle p-3 text-xs">{JSON.stringify(entry.changes.before, null, 2)}</pre>
                </div>
              ) : null}
              {entry.changes.after ? (
                <div>
                  <p className="text-xs font-semibold text-muted">After</p>
                  <pre className="mt-1 overflow-x-auto rounded-xl border border-border bg-subtle p-3 text-xs">{JSON.stringify(entry.changes.after, null, 2)}</pre>
                </div>
              ) : null}
            </section>
          ) : <p className="text-sm text-muted">No structured change record for this event.</p>}
        </div>
      </aside>
    </div>
    </Portal>
  )
}
