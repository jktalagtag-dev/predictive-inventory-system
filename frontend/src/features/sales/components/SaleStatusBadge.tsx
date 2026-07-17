import type { SaleStatus } from '@/features/sales/types/sale'
import { Badge, type BadgeTone } from '@/shared/components/Badge'

const statusStyles: Record<SaleStatus, { label: string; tone: BadgeTone }> = {
  completed: { label: 'Completed', tone: 'success' },
  voided: { label: 'Voided', tone: 'danger' },
  refunded: { label: 'Refunded', tone: 'warning' },
}

export function SaleStatusBadge({ status }: { status: SaleStatus }) {
  const style = statusStyles[status]
  return <Badge tone={style.tone}>{style.label}</Badge>
}
