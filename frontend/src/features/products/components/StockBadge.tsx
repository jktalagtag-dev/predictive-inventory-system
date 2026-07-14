import type { StockStatus } from '@/features/products/types/product'

const stockStyles: Record<StockStatus, { label: string; className: string }> = {
  in_stock: { label: 'In stock', className: 'bg-emerald-50 text-emerald-700' },
  low_stock: { label: 'Low stock', className: 'bg-amber-50 text-amber-700' },
  critical_stock: { label: 'Critical stock', className: 'bg-red-50 text-red-700' },
  out_of_stock: { label: 'Out of stock', className: 'bg-slate-100 text-slate-700' },
}

export function StockBadge({ status }: { status: StockStatus }) {
  const style = stockStyles[status]
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style.className}`}>{style.label}</span>
}
