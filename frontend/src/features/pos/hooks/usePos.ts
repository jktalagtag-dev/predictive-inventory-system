import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { finalizeSale, getPosProducts, posProductQueryKeys } from '@/features/pos/api/posApi'
import { saleQueryKeys } from '@/features/sales/api/salesApi'
import type { FinalizeSalePayload } from '@/features/pos/types/pos'

export function usePosProducts(branchId: string | null, query: string) {
  return useQuery({
    queryKey: posProductQueryKeys.list(branchId, query),
    queryFn: () => getPosProducts(branchId as string, query),
    enabled: branchId !== null,
  })
}

export function useFinalizeSale() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (payload: FinalizeSalePayload) => finalizeSale(payload),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: saleQueryKeys.lists() })
      void queryClient.invalidateQueries({ queryKey: ['products'] })
      void queryClient.invalidateQueries({ queryKey: ['inventory-balances'] })
      void queryClient.invalidateQueries({ queryKey: ['pos-products'] })
    },
  })
}
