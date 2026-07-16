import type { ProductStock } from '@/features/products/types/product'

export function StockBadge({ stock }: { stock: ProductStock | null }) {
  if (!stock) {
    return <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Not tracked</span>
  }

  const available = Number(stock.availableQuantity)
  const style = available <= 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'
  const label = available <= 0 ? 'Out of stock' : `${stock.availableQuantity} available`

  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${style}`}>{label}</span>
}
