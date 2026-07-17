import type { ColdStartStatus } from '@/features/forecasting/types/forecast'

const statusStyles: Record<ColdStartStatus, { label: string; className: string }> = {
  sufficient_history: { label: 'Sufficient history', className: 'bg-emerald-50 text-emerald-700' },
  insufficient_history: { label: 'Insufficient history', className: 'bg-amber-50 text-amber-700' },
  manual_override: { label: 'Manual override', className: 'bg-blue-50 text-blue-700' },
}

export function ColdStartBadge({ status }: { status: ColdStartStatus }) {
  const style = statusStyles[status]
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style.className}`}>{style.label}</span>
}
