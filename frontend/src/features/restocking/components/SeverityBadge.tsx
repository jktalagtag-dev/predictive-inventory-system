import type { AlertSeverity } from '@/features/restocking/types/restocking'

const styles: Record<AlertSeverity, { label: string; className: string }> = {
  low: { label: 'Low', className: 'bg-slate-100 text-slate-700' },
  medium: { label: 'Medium', className: 'bg-amber-50 text-amber-700' },
  high: { label: 'High', className: 'bg-orange-50 text-orange-700' },
  critical: { label: 'Critical', className: 'bg-red-50 text-red-700' },
}

export function SeverityBadge({ severity }: { severity: AlertSeverity }) {
  const style = styles[severity]
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style.className}`}>{style.label}</span>
}
