import { useQuery } from '@tanstack/react-query'
import { getGoodsReceipt, getGoodsReceipts, goodsReceiptQueryKeys } from '@/features/receiving/api/goodsReceiptsApi'
import type { GoodsReceiptFilters } from '@/features/receiving/types/goodsReceipt'

export function useGoodsReceipts(filters: GoodsReceiptFilters) {
  return useQuery({ queryKey: goodsReceiptQueryKeys.list(filters), queryFn: () => getGoodsReceipts(filters), enabled: filters.branchId !== null })
}

export function useGoodsReceipt(id: string | undefined) {
  return useQuery({ queryKey: goodsReceiptQueryKeys.detail(id ?? ''), queryFn: () => getGoodsReceipt(id as string), enabled: id !== undefined })
}
