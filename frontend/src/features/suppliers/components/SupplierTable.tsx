import { Edit3 } from 'lucide-react'
import type { Supplier } from '@/features/suppliers/types/supplier'
import { Button } from '@/shared/components/Button'

export function SupplierTable({ suppliers, onEdit }: { suppliers: Supplier[]; onEdit: (supplier: Supplier) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[850px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr><th className="px-5 py-3">Supplier</th><th className="px-5 py-3">Code</th><th className="px-5 py-3">Contact</th><th className="px-5 py-3">Currency</th><th className="px-5 py-3">Status</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody className="divide-y divide-border">
            {suppliers.map((supplier) => (
              <tr key={supplier.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4 font-semibold text-ink">{supplier.legalName}</td>
                <td className="px-5 py-4 font-mono text-xs text-muted">{supplier.code}</td>
                <td className="px-5 py-4 text-muted">{supplier.email ?? supplier.phone ?? '—'}</td>
                <td className="px-5 py-4 text-muted">{supplier.defaultCurrencyCode}</td>
                <td className="px-5 py-4"><span className={supplier.isActive ? 'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700' : 'inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700'}>{supplier.isActive ? 'Active' : 'Inactive'}</span></td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`Edit ${supplier.legalName}`} size="icon" variant="ghost" onClick={() => onEdit(supplier)}><Edit3 aria-hidden="true" size={16} /></Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
