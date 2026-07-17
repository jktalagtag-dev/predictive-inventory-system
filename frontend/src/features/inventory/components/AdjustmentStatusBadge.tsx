import type { AdjustmentStatus } from '@/features/inventory/types/inventory'

const statusStyles: Record<AdjustmentStatus, { label: string; className: string }> = {
  draft: { label: 'Draft', className: 'bg-slate-100 text-slate-700' },
  pending_approval: { label: 'Pending approval', className: 'bg-amber-50 text-amber-700' },
  posted: { label: 'Posted', className: 'bg-emerald-50 text-emerald-700' },
  rejected: { label: 'Rejected', className: 'bg-red-50 text-red-700' },
  reversed: { label: 'Reversed', className: 'bg-red-50 text-red-700' },
}

export function AdjustmentStatusBadge({ status, isApproved }: { status: AdjustmentStatus; isApproved?: boolean }) {
  const style = statusStyles[status]
  const label = status === 'pending_approval' && isApproved ? 'Approved · awaiting post' : style.label

  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style.className}`}>{label}</span>
}
