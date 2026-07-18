import type { AlertSeverity } from '@/features/restocking/types/restocking'

const styles: Record<AlertSeverity, { label: string; className: string }> = {
  low: { label: 'Low', className: 'bg-subtle text-muted' },
  medium: { label: 'Medium', className: 'bg-warning/10 text-warning-text' },
  high: { label: 'High', className: 'bg-warning/20 text-warning-text font-bold' },
  critical: { label: 'Critical', className: 'bg-danger/10 text-danger-text' },
}

export function SeverityBadge({ severity }: { severity: AlertSeverity }) {
  const style = styles[severity]
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style.className}`}>{style.label}</span>
}
