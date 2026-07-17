import type { ColdStartStatus } from '@/features/forecasting/types/forecast'
import { Badge, type BadgeTone } from '@/shared/components/Badge'

const statusStyles: Record<ColdStartStatus, { label: string; tone: BadgeTone }> = {
  sufficient_history: { label: 'Sufficient history', tone: 'success' },
  insufficient_history: { label: 'Insufficient history', tone: 'warning' },
  manual_override: { label: 'Manual override', tone: 'info' },
}

export function ColdStartBadge({ status }: { status: ColdStartStatus }) {
  const style = statusStyles[status]
  return <Badge tone={style.tone}>{style.label}</Badge>
}
