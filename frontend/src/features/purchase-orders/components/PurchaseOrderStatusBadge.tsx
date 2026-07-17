import type { PurchaseOrderStatus } from '@/features/purchase-orders/types/purchaseOrder'
import { Badge, type BadgeTone } from '@/shared/components/Badge'

const statusStyles: Record<PurchaseOrderStatus, { label: string; tone: BadgeTone }> = {
  draft: { label: 'Draft', tone: 'neutral' },
  submitted: { label: 'Submitted', tone: 'warning' },
  approved: { label: 'Approved', tone: 'info' },
  ordered: { label: 'Ordered', tone: 'info' },
  partially_received: { label: 'Partially received', tone: 'warning' },
  received: { label: 'Received', tone: 'success' },
  cancelled: { label: 'Cancelled', tone: 'danger' },
  closed: { label: 'Closed', tone: 'success' },
}

export function PurchaseOrderStatusBadge({ status }: { status: PurchaseOrderStatus }) {
  const style = statusStyles[status]
  return <Badge tone={style.tone}>{style.label}</Badge>
}
