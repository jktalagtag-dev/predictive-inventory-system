import { PanelRightOpen } from 'lucide-react'
import type { ReorderPolicy } from '@/features/restocking/types/restocking'
import { Button } from '@/shared/components/Button'

export function ReorderPolicyTable({ policies, onView }: { policies: ReorderPolicy[]; onView: (policy: ReorderPolicy) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[750px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr><th className="px-5 py-3">Product</th><th className="px-5 py-3 text-right">Safety stock</th><th className="px-5 py-3 text-right">Lead time (days)</th><th className="px-5 py-3 text-right">Reorder point</th><th className="px-5 py-3">State</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {policies.map((policy) => (
              <tr key={policy.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4"><p className="font-medium text-ink">{policy.productName ?? '—'}</p><p className="font-mono text-xs text-muted">{policy.productSku}</p></td>
                <td className="px-5 py-4 text-right tabular-nums">{policy.safetyStockQuantity}</td>
                <td className="px-5 py-4 text-right tabular-nums">{policy.leadTimeDaysOverride ?? '—'}</td>
                <td className="px-5 py-4 text-right tabular-nums font-semibold text-ink">{policy.reorderPointQuantity ?? 'Not calculated'}</td>
                <td className="px-5 py-4"><span className={policy.isActive ? 'text-sm font-medium text-emerald-700' : 'text-sm font-medium text-slate-600'}>{policy.isActive ? 'Active' : 'Inactive'}</span></td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`View policy for ${policy.productName}`} size="icon" variant="ghost" onClick={() => onView(policy)}><PanelRightOpen aria-hidden="true" size={17} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
