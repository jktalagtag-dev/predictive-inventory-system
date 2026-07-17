import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuth } from '@/features/auth/AuthProvider'
import { getSale, refundSale, saleQueryKeys, voidSale } from '@/features/sales/api/salesApi'
import { SaleDetailsDrawer } from '@/features/sales/components/SaleDetailsDrawer'
import { SaleTable } from '@/features/sales/components/SaleTable'
import { useSales } from '@/features/sales/hooks/useSales'
import type { RefundLineInput, RefundPaymentInput, Sale, SaleFilters, SaleStatus } from '@/features/sales/types/sale'
import { type ApiError } from '@/shared/api/client'
import { PageHeader } from '@/shared/components/PageHeader'

const defaultFilters: SaleFilters = { branchId: null, status: 'all', saleNumber: '', page: 1, perPage: 10 }

export default function SalesPage() {
  const { session } = useAuth()
  const [filters, setFilters] = useState<SaleFilters>(defaultFilters)
  const [selectedSaleId, setSelectedSaleId] = useState<string | undefined>()
  const queryClient = useQueryClient()

  const defaultBranchId = (session?.user.branches.find((branch) => branch.isDefault) ?? session?.user.branches[0])?.id
  useEffect(() => {
    if (!defaultBranchId) return
    setFilters((state) => (state.branchId === defaultBranchId ? state : { ...state, branchId: defaultBranchId }))
  }, [defaultBranchId])

  const salesQuery = useSales(filters)
  const selectedSaleQuery = useQuery({
    queryKey: saleQueryKeys.detail(selectedSaleId ?? ''),
    queryFn: () => getSale(selectedSaleId as string),
    enabled: selectedSaleId !== undefined,
  })

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: saleQueryKeys.lists() })
    void queryClient.invalidateQueries({ queryKey: ['inventory-balances'] })
    if (selectedSaleId) void queryClient.invalidateQueries({ queryKey: saleQueryKeys.detail(selectedSaleId) })
  }

  const voidMutation = useMutation({ mutationFn: ({ sale, reason }: { sale: Sale; reason: string }) => voidSale(sale, reason), onSuccess: invalidate })
  const refundMutation = useMutation({
    mutationFn: ({ sale, reason, lines, payments }: { sale: Sale; reason: string; lines: RefundLineInput[]; payments: RefundPaymentInput[] }) =>
      refundSale(sale, reason, lines, payments),
    onSuccess: invalidate,
  })

  const isActing = voidMutation.isPending || refundMutation.isPending
  const error = (voidMutation.error ?? refundMutation.error) as ApiError | null

  return (
    <div className="space-y-6">
      <PageHeader description="Review completed sales, then void or refund as authorized." title="Sales" />
      {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <section className="grid gap-3 rounded-xl border border-border bg-surface p-4 shadow-panel md:grid-cols-[minmax(0,1fr)_200px]">
        <input className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" placeholder="Search by sale number" value={filters.saleNumber} onChange={(event) => setFilters((state) => ({ ...state, saleNumber: event.target.value, page: 1 }))} />
        <select className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.status} onChange={(event) => setFilters((state) => ({ ...state, status: event.target.value as SaleStatus | 'all', page: 1 }))}>
          <option value="all">All statuses</option>
          <option value="completed">Completed</option>
          <option value="voided">Voided</option>
          <option value="refunded">Refunded</option>
        </select>
      </section>

      <p className="text-sm text-muted">{salesQuery.data?.meta.total ?? 0} sales {salesQuery.isFetching ? '· Updating…' : ''}</p>
      <SaleTable sales={salesQuery.data?.data ?? []} onView={(sale) => setSelectedSaleId(sale.id)} />

      {selectedSaleQuery.data ? (
        <SaleDetailsDrawer
          isActing={isActing}
          sale={selectedSaleQuery.data}
          onClose={() => setSelectedSaleId(undefined)}
          onRefund={(reason, lines, payments) => refundMutation.mutate({ sale: selectedSaleQuery.data, reason, lines, payments })}
          onVoid={(reason) => voidMutation.mutate({ sale: selectedSaleQuery.data, reason })}
        />
      ) : null}
    </div>
  )
}
