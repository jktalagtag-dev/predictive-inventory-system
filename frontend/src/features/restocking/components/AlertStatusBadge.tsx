import type { AlertStatus } from '@/features/restocking/types/restocking'

const styles: Record<AlertStatus, { label: string; className: string }> = {
  active: { label: 'Active', className: 'bg-red-50 text-red-700' },
  acknowledged: { label: 'Acknowledged', className: 'bg-amber-50 text-amber-700' },
  resolved: { label: 'Resolved', className: 'bg-emerald-50 text-emerald-700' },
  dismissed: { label: 'Dismissed', className: 'bg-slate-100 text-slate-700' },
}

export function AlertStatusBadge({ status }: { status: AlertStatus }) {
  const style = styles[status]
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style.className}`}>{style.label}</span>
}
