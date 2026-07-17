import { type FormEvent, useState } from 'react'
import { X } from 'lucide-react'
import { Button } from '@/shared/components/Button'

type ReasonPromptDialogProps = {
  title: string
  description: string
  confirmLabel: string
  isSubmitting: boolean
  onClose: () => void
  onConfirm: (reason: string) => void
}

export function ReasonPromptDialog({ title, description, confirmLabel, isSubmitting, onClose, onConfirm }: ReasonPromptDialogProps) {
  const [reason, setReason] = useState('')
  const submit = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); onConfirm(reason) }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation" onMouseDown={(event) => event.stopPropagation()}>
      <section aria-labelledby="sale-reason-prompt-title" aria-modal="true" className="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-panel" role="dialog">
        <div className="flex items-start justify-between gap-4">
          <h2 id="sale-reason-prompt-title" className="text-lg font-bold text-ink">{title}</h2>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>
        <p className="mt-2 text-sm text-muted">{description}</p>
        <form className="mt-4 space-y-4" onSubmit={submit}>
          <label className="block text-sm font-semibold text-ink">Reason
            <textarea autoFocus className="mt-2 w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required rows={3} value={reason} onChange={(event) => setReason(event.target.value)} />
          </label>
          <div className="flex justify-end gap-3 border-t border-border pt-5">
            <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
            <Button disabled={isSubmitting || reason.trim() === ''} type="submit" variant="danger">{isSubmitting ? 'Submitting' : confirmLabel}</Button>
          </div>
        </form>
      </section>
    </div>
  )
}
