import type { PurchaseOrderStatus } from '@/features/purchase-orders/types/purchaseOrder'

const statusStyles: Record<PurchaseOrderStatus, { label: string; className: string }> = {
  draft: { label: 'Draft', className: 'bg-slate-100 text-slate-700' },
  submitted: { label: 'Submitted', className: 'bg-amber-50 text-amber-700' },
  approved: { label: 'Approved', className: 'bg-brand-50 text-brand-700' },
  ordered: { label: 'Ordered', className: 'bg-violet-50 text-violet-700' },
  partially_received: { label: 'Partially received', className: 'bg-amber-50 text-amber-700' },
  received: { label: 'Received', className: 'bg-emerald-50 text-emerald-700' },
  cancelled: { label: 'Cancelled', className: 'bg-red-50 text-red-700' },
  closed: { label: 'Closed', className: 'bg-emerald-50 text-emerald-700' },
}

export function PurchaseOrderStatusBadge({ status }: { status: PurchaseOrderStatus }) {
  const style = statusStyles[status]
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style.className}`}>{style.label}</span>
}
