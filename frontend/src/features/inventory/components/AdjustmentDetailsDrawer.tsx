import { useState } from 'react'
import { X } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthProvider'
import { getAdjustmentActions } from '@/features/inventory/lib/adjustmentWorkflow'
import type { InventoryAdjustment } from '@/features/inventory/types/inventory'
import { AdjustmentStatusBadge } from '@/features/inventory/components/AdjustmentStatusBadge'
import { ReasonPromptDialog } from '@/features/inventory/components/ReasonPromptDialog'
import { Button } from '@/shared/components/Button'
import { drawerPanelClass } from '@/shared/lib/modalClasses'

type AdjustmentDetailsDrawerProps = {
  adjustment: InventoryAdjustment
  isActing: boolean
  onClose: () => void
  onApprove: () => void
  onPost: () => void
  onReverse: (reason: string) => void
}

export function AdjustmentDetailsDrawer({ adjustment, isActing, onClose, onApprove, onPost, onReverse }: AdjustmentDetailsDrawerProps) {
  const { hasPermission } = useAuth()
  const [showReversePrompt, setShowReversePrompt] = useState(false)
  const actions = getAdjustmentActions(adjustment)

  return (
    <div className="fixed inset-0 z-40 bg-slate-950/30" role="presentation" onMouseDown={onClose}>
      <aside aria-labelledby="adj-details-title" aria-modal="true" className={drawerPanelClass()} role="dialog" onMouseDown={(event) => event.stopPropagation()}>
        <header className="flex items-start justify-between gap-4 border-b border-border p-4 sm:p-6">
          <div>
            <p className="font-mono text-xs text-muted">{adjustment.adjustmentNumber}</p>
            <h2 id="adj-details-title" className="mt-1 text-xl font-bold tracking-tight text-ink capitalize">{adjustment.reasonCode.replace('_', ' ')}</h2>
            <div className="mt-3"><AdjustmentStatusBadge isApproved={adjustment.approvedAt !== null} status={adjustment.status} /></div>
          </div>
          <Button aria-label="Close adjustment details" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </header>
        <div className="flex-1 space-y-6 overflow-y-auto p-4 sm:p-6">
          <section>
            <h3 className="text-sm font-semibold text-ink">Lines</h3>
            <div className="mt-3 space-y-2 sm:hidden">
              {adjustment.lines.map((line) => (
                <div className="rounded-xl border border-border p-3 text-sm" key={line.id}>
                  <p className="font-medium text-ink">{line.productName}</p>
                  <p className="text-xs text-muted">{line.productSku}</p>
                  <dl className="mt-2 grid grid-cols-3 gap-2 text-xs">
                    <div><dt className="text-muted">Before</dt><dd className="tabular-nums text-ink">{line.beforeQuantity}</dd></div>
                    <div><dt className="text-muted">Delta</dt><dd className={`tabular-nums font-semibold ${Number(line.quantityDelta) > 0 ? 'text-success-text' : 'text-danger-text'}`}>{Number(line.quantityDelta) > 0 ? '+' : ''}{line.quantityDelta}</dd></div>
                    <div><dt className="text-muted">After</dt><dd className="tabular-nums text-ink">{line.afterQuantity}</dd></div>
                  </dl>
                </div>
              ))}
            </div>
            <div className="mt-3 hidden overflow-x-auto rounded-xl border border-border sm:block">
              <table className="w-full min-w-[550px] text-sm">
                <thead className="bg-subtle text-left text-xs font-semibold text-muted"><tr><th className="px-3 py-2">Product</th><th className="px-3 py-2 text-right">Before</th><th className="px-3 py-2 text-right">Delta</th><th className="px-3 py-2 text-right">After</th></tr></thead>
                <tbody className="divide-y divide-border">
                  {adjustment.lines.map((line) => (
                    <tr key={line.id}>
                      <td className="px-3 py-2"><p className="font-medium text-ink">{line.productName}</p><p className="text-xs text-muted">{line.productSku}</p></td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.beforeQuantity}</td>
                      <td className={`px-3 py-2 text-right tabular-nums font-semibold ${Number(line.quantityDelta) > 0 ? 'text-success-text' : 'text-danger-text'}`}>
                        {Number(line.quantityDelta) > 0 ? '+' : ''}{line.quantityDelta}
                      </td>
                      <td className="px-3 py-2 text-right tabular-nums">{line.afterQuantity}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
          {adjustment.reasonNote ? <section><h3 className="text-sm font-semibold text-ink">Notes</h3><p className="mt-2 whitespace-pre-wrap text-sm text-muted">{adjustment.reasonNote}</p></section> : null}
        </div>
        <footer className="flex flex-wrap gap-2 border-t border-border p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:p-6">
          {actions.canApprove && hasPermission('inventory.adjustments.approve') ? <Button disabled={isActing} onClick={onApprove}>Approve</Button> : null}
          {actions.canPost && hasPermission('inventory.adjustments.post') ? <Button disabled={isActing} onClick={onPost}>Post</Button> : null}
          {actions.canReverse && hasPermission('inventory.adjustments.reverse') ? <Button disabled={isActing} variant="danger" onClick={() => setShowReversePrompt(true)}>Reverse</Button> : null}
        </footer>
      </aside>
      {showReversePrompt ? (
        <ReasonPromptDialog
          confirmLabel="Reverse adjustment"
          description="This creates a compensating adjustment that reverses the posted stock movement and cannot be undone."
          isSubmitting={isActing}
          title="Reverse inventory adjustment"
          onClose={() => setShowReversePrompt(false)}
          onConfirm={(reason) => { onReverse(reason); setShowReversePrompt(false) }}
        />
      ) : null}
    </div>
  )
}
