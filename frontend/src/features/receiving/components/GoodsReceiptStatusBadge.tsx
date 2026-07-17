import type { GoodsReceiptStatus } from '@/features/receiving/types/goodsReceipt'
import { Badge, type BadgeTone } from '@/shared/components/Badge'

const statusStyles: Record<GoodsReceiptStatus, { label: string; tone: BadgeTone }> = {
  draft: { label: 'Draft', tone: 'neutral' },
  posted: { label: 'Posted', tone: 'success' },
  reversed: { label: 'Reversed', tone: 'danger' },
}

export function GoodsReceiptStatusBadge({ status }: { status: GoodsReceiptStatus }) {
  const style = statusStyles[status]
  return <Badge tone={style.tone}>{style.label}</Badge>
}
