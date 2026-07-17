import type { GoodsReceiptStatus } from '@/features/receiving/types/goodsReceipt'

const statusStyles: Record<GoodsReceiptStatus, { label: string; className: string }> = {
  draft: { label: 'Draft', className: 'bg-slate-100 text-slate-700' },
  posted: { label: 'Posted', className: 'bg-emerald-50 text-emerald-700' },
  reversed: { label: 'Reversed', className: 'bg-red-50 text-red-700' },
}

export function GoodsReceiptStatusBadge({ status }: { status: GoodsReceiptStatus }) {
  const style = statusStyles[status]
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style.className}`}>{style.label}</span>
}
