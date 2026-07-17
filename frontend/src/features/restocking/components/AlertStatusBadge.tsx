import type { AlertStatus } from '@/features/restocking/types/restocking'
import { Badge, type BadgeTone } from '@/shared/components/Badge'

const styles: Record<AlertStatus, { label: string; tone: BadgeTone }> = {
  active: { label: 'Active', tone: 'danger' },
  acknowledged: { label: 'Acknowledged', tone: 'warning' },
  resolved: { label: 'Resolved', tone: 'success' },
  dismissed: { label: 'Dismissed', tone: 'neutral' },
}

export function AlertStatusBadge({ status }: { status: AlertStatus }) {
  const style = styles[status]
  return <Badge tone={style.tone}>{style.label}</Badge>
}
