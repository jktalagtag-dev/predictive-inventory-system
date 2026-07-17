import type { ProductStock } from '@/features/products/types/product'
import { Badge } from '@/shared/components/Badge'

export function StockBadge({ stock }: { stock: ProductStock | null }) {
  if (!stock) {
    return <Badge tone="neutral">Not tracked</Badge>
  }

  const available = Number(stock.availableQuantity)

  return <Badge tone={available <= 0 ? 'danger' : 'success'}>{available <= 0 ? 'Out of stock' : `${stock.availableQuantity} available`}</Badge>
}
