import { PanelRightOpen } from 'lucide-react'
import type { ReorderPolicy } from '@/features/restocking/types/restocking'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

const stateBadge = (isActive: boolean) => (
  <span className={isActive ? 'inline-flex rounded-full bg-success/10 px-2.5 py-1 text-xs font-semibold text-success-text' : 'inline-flex rounded-full bg-subtle px-2.5 py-1 text-xs font-semibold text-muted'}>
    {isActive ? 'Active' : 'Inactive'}
  </span>
)

export function ReorderPolicyTable({ policies, onView }: { policies: ReorderPolicy[]; onView: (policy: ReorderPolicy) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {policies.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No reorder policies match these filters.</p>
        ) : (
          policies.map((policy) => (
            <RecordCard
              key={policy.id}
              ariaLabel={`View policy for ${policy.productName}`}
              badge={stateBadge(policy.isActive)}
              title={policy.productName ?? '—'}
              subtitle={<span className="font-mono">{policy.productSku}</span>}
              fields={[
                { label: 'Safety stock', value: policy.safetyStockQuantity },
                { label: 'Lead time (days)', value: policy.leadTimeDaysOverride ?? '—' },
                { label: 'Reorder point', value: policy.reorderPointQuantity ?? 'Not calculated', full: true },
              ]}
              onClick={() => onView(policy)}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={750}>
          <TableHead>
            <tr>
              <TableHeaderCell>Product</TableHeaderCell>
              <TableHeaderCell align="right">Safety stock</TableHeaderCell>
              <TableHeaderCell align="right">Lead time (days)</TableHeaderCell>
              <TableHeaderCell align="right">Reorder point</TableHeaderCell>
              <TableHeaderCell>State</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {policies.length === 0 ? (
              <TableEmptyState colSpan={6}>No reorder policies match these filters.</TableEmptyState>
            ) : (
              policies.map((policy) => (
                <TableRow key={policy.id}>
                  <TableCell>
                    <p className="font-medium text-ink">{policy.productName ?? '—'}</p>
                    <p className="font-mono text-xs text-muted">{policy.productSku}</p>
                  </TableCell>
                  <TableCell align="right">{policy.safetyStockQuantity}</TableCell>
                  <TableCell align="right">{policy.leadTimeDaysOverride ?? '—'}</TableCell>
                  <TableCell align="right" className="font-semibold text-ink">{policy.reorderPointQuantity ?? 'Not calculated'}</TableCell>
                  <TableCell>
                    <span className={policy.isActive ? 'text-sm font-medium text-success-text' : 'text-sm font-medium text-muted'}>
                      {policy.isActive ? 'Active' : 'Inactive'}
                    </span>
                  </TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`View policy for ${policy.productName}`} size="icon" variant="ghost" onClick={() => onView(policy)}><PanelRightOpen aria-hidden="true" size={18} /></Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </>
  )
}
