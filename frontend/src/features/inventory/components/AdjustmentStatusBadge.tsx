import type { AdjustmentStatus } from '@/features/inventory/types/inventory'
import { Badge, type BadgeTone } from '@/shared/components/Badge'

const statusStyles: Record<AdjustmentStatus, { label: string; tone: BadgeTone }> = {
  draft: { label: 'Draft', tone: 'neutral' },
  pending_approval: { label: 'Pending approval', tone: 'warning' },
  posted: { label: 'Posted', tone: 'success' },
  rejected: { label: 'Rejected', tone: 'danger' },
  reversed: { label: 'Reversed', tone: 'danger' },
}

export function AdjustmentStatusBadge({ status, isApproved }: { status: AdjustmentStatus; isApproved?: boolean }) {
  const style = statusStyles[status]
  const label = status === 'pending_approval' && isApproved ? 'Approved · awaiting post' : style.label

  return <Badge tone={style.tone}>{label}</Badge>
}
