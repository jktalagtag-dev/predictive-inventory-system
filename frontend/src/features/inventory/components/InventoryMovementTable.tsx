import type { InventoryMovement } from '@/features/inventory/types/inventory'

const movementTypeLabels: Record<InventoryMovement['movementType'], string> = {
  receipt: 'Receipt',
  sale: 'Sale',
  adjustment: 'Adjustment',
  return: 'Return',
  reservation: 'Reservation',
  release: 'Release',
  reversal: 'Reversal',
}

export function InventoryMovementTable({ movements }: { movements: InventoryMovement[] }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[800px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr>
              <th className="px-5 py-3">Product</th>
              <th className="px-5 py-3">Type</th>
              <th className="px-5 py-3 text-right">Quantity</th>
              <th className="px-5 py-3 text-right">Balance after</th>
              <th className="px-5 py-3">Reference</th>
              <th className="px-5 py-3">Actor</th>
              <th className="px-5 py-3">Effective</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {movements.map((movement) => {
              const isPositive = Number(movement.quantityDelta) > 0
              return (
                <tr key={movement.id} className="hover:bg-subtle/70">
                  <td className="px-5 py-4">
                    <p className="font-medium text-ink">{movement.product?.name ?? '—'}</p>
                    <p className="text-xs text-muted">{movement.product?.sku ?? '—'}</p>
                  </td>
                  <td className="px-5 py-4 text-muted">{movementTypeLabels[movement.movementType]}</td>
                  <td className={`px-5 py-4 text-right tabular-nums font-semibold ${isPositive ? 'text-emerald-700' : 'text-red-700'}`}>
                    {isPositive ? '+' : ''}{movement.quantityDelta}
                  </td>
                  <td className="px-5 py-4 text-right tabular-nums text-muted">{movement.onHandAfterQuantity ?? '—'}</td>
                  <td className="px-5 py-4 font-mono text-xs text-muted">{movement.referenceType} #{movement.referenceId}</td>
                  <td className="px-5 py-4 text-muted">{movement.actor?.displayName ?? '—'}</td>
                  <td className="px-5 py-4 text-muted">{movement.effectiveAt ? new Date(movement.effectiveAt).toLocaleString() : '—'}</td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
    </section>
  )
}
