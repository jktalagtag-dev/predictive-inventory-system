import { RotateCcw, Trash2, X } from 'lucide-react'
import type { QueuedOperation } from '@/shared/offline/db'
import { Button } from '@/shared/components/Button'

type ConflictResolverDialogProps = {
  operation: QueuedOperation
  onClose: () => void
  onDiscard: () => void
  onRetry: () => void
}

/**
 * Presents local vs. server context for a rejected or conflicted
 * operation and the two permitted resolution actions
 * (DEVELOPMENT_ROADMAP.md M9 acceptance criteria: "Conflict UI presents
 * local and server data, authorship/time context, permitted resolution
 * actions"). Retrying resubmits as a new operation rather than mutating
 * the original — the original stays in place as an immutable record of
 * what was refused and why.
 */
export function ConflictResolverDialog({ operation, onClose, onDiscard, onRetry }: ConflictResolverDialogProps) {
  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation">
      <section aria-labelledby="conflict-resolver-title" aria-modal="true" className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-border bg-surface p-6 shadow-panel" role="dialog">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h2 id="conflict-resolver-title" className="text-lg font-bold text-ink">{operation.summary}</h2>
            <p className="mt-1 text-sm text-muted">
              Queued {new Date(operation.createdAt).toLocaleString()}
              {operation.lastAttemptAt ? ` · Last attempted ${new Date(operation.lastAttemptAt).toLocaleString()}` : ''}
            </p>
          </div>
          <Button aria-label="Close" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>

        <div className="mt-6 space-y-4">
          {operation.errorCode ? (
            <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
              <p className="font-semibold">{operation.errorCode}</p>
            </div>
          ) : null}

          <div>
            <p className="text-xs font-semibold text-muted">Local (queued on this device)</p>
            <pre className="mt-1 overflow-x-auto rounded-lg border border-border bg-subtle p-3 text-xs">{JSON.stringify(operation.payload, null, 2)}</pre>
          </div>

          {operation.conflictPayload ? (
            <div>
              <p className="text-xs font-semibold text-muted">Server</p>
              <pre className="mt-1 overflow-x-auto rounded-lg border border-border bg-subtle p-3 text-xs">{JSON.stringify(operation.conflictPayload, null, 2)}</pre>
            </div>
          ) : null}

          <div className="flex justify-end gap-3 border-t border-border pt-5">
            <Button variant="secondary" onClick={onDiscard}><Trash2 aria-hidden="true" size={16} /> Discard</Button>
            <Button onClick={onRetry}><RotateCcw aria-hidden="true" size={16} /> Retry as new</Button>
          </div>
        </div>
      </section>
    </div>
  )
}
