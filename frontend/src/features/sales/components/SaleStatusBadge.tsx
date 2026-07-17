import type { SaleStatus } from '@/features/sales/types/sale'

const statusStyles: Record<SaleStatus, { label: string; className: string }> = {
  completed: { label: 'Completed', className: 'bg-emerald-50 text-emerald-700' },
  voided: { label: 'Voided', className: 'bg-red-50 text-red-700' },
  refunded: { label: 'Refunded', className: 'bg-amber-50 text-amber-700' },
}

export function SaleStatusBadge({ status }: { status: SaleStatus }) {
  const style = statusStyles[status]
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style.className}`}>{style.label}</span>
}
