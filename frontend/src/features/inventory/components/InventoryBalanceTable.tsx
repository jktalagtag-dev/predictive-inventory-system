import type { InventoryBalance } from '@/features/inventory/types/inventory'

export function InventoryBalanceTable({ balances }: { balances: InventoryBalance[] }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[750px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr>
              <th className="px-5 py-3">Product</th>
              <th className="px-5 py-3 text-right">On hand</th>
              <th className="px-5 py-3 text-right">Reserved</th>
              <th className="px-5 py-3 text-right">Available</th>
              <th className="px-5 py-3 text-right">Incoming</th>
              <th className="px-5 py-3">Last movement</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {balances.map((balance) => (
              <tr key={balance.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4">
                  <p className="font-medium text-ink">{balance.product?.name ?? '—'}</p>
                  <p className="text-xs text-muted">{balance.product?.sku ?? '—'}</p>
                </td>
                <td className="px-5 py-4 text-right tabular-nums text-ink">{balance.onHandQuantity}</td>
                <td className="px-5 py-4 text-right tabular-nums text-muted">{balance.reservedQuantity}</td>
                <td className={`px-5 py-4 text-right tabular-nums font-semibold ${Number(balance.availableQuantity) <= 0 ? 'text-red-700' : 'text-ink'}`}>{balance.availableQuantity}</td>
                <td className="px-5 py-4 text-right tabular-nums text-muted">{balance.incomingQuantity}</td>
                <td className="px-5 py-4 text-muted">{balance.lastMovementAt ? new Date(balance.lastMovementAt).toLocaleString() : '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
