import { RotateCcw, Trash2 } from 'lucide-react'
import type { QueuedOperation } from '@/shared/offline/db'
import { Button } from '@/shared/components/Button'
import { Dialog } from '@/shared/components/Dialog'

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
    <Dialog
      description={`Queued ${new Date(operation.createdAt).toLocaleString()}${operation.lastAttemptAt ? ` · Last attempted ${new Date(operation.lastAttemptAt).toLocaleString()}` : ''}`}
      footer={(
        <>
          <Button variant="secondary" onClick={onDiscard}><Trash2 aria-hidden="true" size={16} /> Discard</Button>
          <Button onClick={onRetry}><RotateCcw aria-hidden="true" size={16} /> Retry as new</Button>
        </>
      )}
      size="lg"
      title={operation.summary}
      onClose={onClose}
    >
      <div className="space-y-4">
        {operation.errorCode ? (
          <div className="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text">
            <p className="font-semibold">{operation.errorCode}</p>
          </div>
        ) : null}

        <div>
          <p className="text-xs font-semibold text-muted">Local (queued on this device)</p>
          <pre className="mt-1 overflow-x-auto rounded-xl border border-border bg-subtle p-3 text-xs">{JSON.stringify(operation.payload, null, 2)}</pre>
        </div>

        {operation.conflictPayload ? (
          <div>
            <p className="text-xs font-semibold text-muted">Server</p>
            <pre className="mt-1 overflow-x-auto rounded-xl border border-border bg-subtle p-3 text-xs">{JSON.stringify(operation.conflictPayload, null, 2)}</pre>
          </div>
        ) : null}
      </div>
    </Dialog>
  )
}
