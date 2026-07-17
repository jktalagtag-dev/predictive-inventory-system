import { useState } from 'react'
import { X } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthProvider'
import { AlertStatusBadge } from '@/features/restocking/components/AlertStatusBadge'
import { ReasonPromptDialog } from '@/features/restocking/components/ReasonPromptDialog'
import { SeverityBadge } from '@/features/restocking/components/SeverityBadge'
import type { RestockingAlert } from '@/features/restocking/types/restocking'
import { Button } from '@/shared/components/Button'

type AlertDetailsDrawerProps = {
  alert: RestockingAlert
  isActing: boolean
  onClose: () => void
  onAcknowledge: () => void
  onResolve: (reason: string) => void
  onDismiss: (reason: string) => void
}

export function AlertDetailsDrawer({ alert, isActing, onClose, onAcknowledge, onResolve, onDismiss }: AlertDetailsDrawerProps) {
  const { hasPermission } = useAuth()
  const [prompt, setPrompt] = useState<'resolve' | 'dismiss' | null>(null)

  const canAcknowledge = alert.status === 'active' && hasPermission('restocking.acknowledge')
  const canResolve = (alert.status === 'active' || alert.status === 'acknowledged') && hasPermission('restocking.resolve')

  return (
    <div className="fixed inset-0 z-40 bg-slate-950/30" role="presentation" onMouseDown={onClose}>
      <aside aria-labelledby="alert-details-title" aria-modal="true" className="ml-auto flex h-full w-full max-w-2xl flex-col border-l border-border bg-surface shadow-panel" role="dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="flex items-start justify-between gap-4 border-b border-border p-6">
          <div>
            <p className="font-mono text-xs text-muted">Alert #{alert.id}</p>
            <h2 id="alert-details-title" className="mt-1 text-xl font-bold tracking-tight text-ink">{alert.productName}</h2>
            <div className="mt-3 flex gap-2"><AlertStatusBadge status={alert.status} /><SeverityBadge severity={alert.severity} /></div>
          </div>
          <Button aria-label="Close alert details" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </header>
        <div className="flex-1 space-y-6 overflow-y-auto p-6">
          <section className="grid grid-cols-2 gap-4 rounded-lg border border-border p-4 text-sm">
            <div><p className="text-muted">Available stock</p><p className="font-semibold text-ink">{alert.availableQuantitySnapshot}</p></div>
            <div><p className="text-muted">Reorder point</p><p className="font-semibold text-ink">{alert.reorderPointSnapshot}</p></div>
            <div><p className="text-muted">Incoming stock</p><p className="font-semibold text-ink">{alert.incomingQuantitySnapshot}</p></div>
            <div><p className="text-muted">Recommended order</p><p className="font-semibold text-ink">{alert.recommendedOrderQuantity ?? '—'}</p></div>
            <div><p className="text-muted">First triggered</p><p className="font-semibold text-ink">{alert.firstTriggeredAt ? new Date(alert.firstTriggeredAt).toLocaleString() : '—'}</p></div>
            <div><p className="text-muted">Last evaluated</p><p className="font-semibold text-ink">{alert.lastEvaluatedAt ? new Date(alert.lastEvaluatedAt).toLocaleString() : '—'}</p></div>
          </section>
          {alert.dismissalReason ? <section><h3 className="text-sm font-semibold text-ink">Dismissal reason</h3><p className="mt-2 text-sm text-muted">{alert.dismissalReason}</p></section> : null}
          <section>
            <h3 className="text-sm font-semibold text-ink">History</h3>
            <ul className="mt-3 space-y-2">
              {alert.events.map((event) => (
                <li key={event.id} className="rounded-lg border border-border px-3 py-2 text-sm">
                  <p className="font-medium capitalize text-ink">{event.eventType.replace('_', ' ')}</p>
                  <p className="text-xs text-muted">{event.occurredAt ? new Date(event.occurredAt).toLocaleString() : '—'}</p>
                </li>
              ))}
            </ul>
          </section>
        </div>
        <footer className="flex flex-wrap gap-2 border-t border-border p-6">
          {canAcknowledge ? <Button disabled={isActing} onClick={onAcknowledge}>Acknowledge</Button> : null}
          {canResolve ? <Button disabled={isActing} onClick={() => setPrompt('resolve')}>Resolve</Button> : null}
          {canResolve ? <Button disabled={isActing} variant="danger" onClick={() => setPrompt('dismiss')}>Dismiss</Button> : null}
        </footer>
      </aside>
      {prompt === 'resolve' ? (
        <ReasonPromptDialog
          confirmLabel="Resolve alert"
          description="Confirm this alert reflects a verified stock recovery or approved replenishment."
          isSubmitting={isActing}
          title="Resolve restocking alert"
          onClose={() => setPrompt(null)}
          onConfirm={(reason) => { onResolve(reason); setPrompt(null) }}
        />
      ) : null}
      {prompt === 'dismiss' ? (
        <ReasonPromptDialog
          confirmLabel="Dismiss alert"
          description="Dismissal is auditable and requires a documented reason."
          isSubmitting={isActing}
          title="Dismiss restocking alert"
          onClose={() => setPrompt(null)}
          onConfirm={(reason) => { onDismiss(reason); setPrompt(null) }}
        />
      ) : null}
    </div>
  )
}
