import { type ChangeEvent, useState } from 'react'
import { Search, Truck } from 'lucide-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { createSupplier, supplierQueryKeys, updateSupplier } from '@/features/suppliers/api/suppliersApi'
import { SupplierFormDialog } from '@/features/suppliers/components/SupplierFormDialog'
import { SupplierTable } from '@/features/suppliers/components/SupplierTable'
import { useSuppliers } from '@/features/suppliers/hooks/useSuppliers'
import type { Supplier, SupplierFilters, SupplierFormValues } from '@/features/suppliers/types/supplier'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

const defaultFilters: SupplierFilters = { search: '', active: 'all', page: 1, perPage: 10 }

export default function SuppliersPage() {
  const [filters, setFilters] = useState<SupplierFilters>(defaultFilters)
  const [editingSupplier, setEditingSupplier] = useState<Supplier | undefined>()
  const [isFormOpen, setIsFormOpen] = useState(false)
  const queryClient = useQueryClient()
  const suppliersQuery = useSuppliers(filters)

  const invalidate = () => queryClient.invalidateQueries({ queryKey: supplierQueryKeys.lists() })
  const createMutation = useMutation({ mutationFn: createSupplier, onSuccess: () => { void invalidate(); setIsFormOpen(false) } })
  const updateMutation = useMutation({ mutationFn: ({ supplier, values }: { supplier: Supplier; values: SupplierFormValues }) => updateSupplier(supplier, values), onSuccess: () => { void invalidate(); setIsFormOpen(false); setEditingSupplier(undefined) } })

  const totalPages = Math.max(1, Math.ceil((suppliersQuery.data?.meta.total ?? 0) / filters.perPage))
  const error = (createMutation.error ?? updateMutation.error ?? suppliersQuery.error) as ApiError | null
  const suppliers = suppliersQuery.data?.data ?? []

  const updateFilter = <K extends keyof SupplierFilters>(key: K, value: SupplierFilters[K]) => setFilters((state) => ({ ...state, [key]: value, page: key === 'page' ? Number(value) : 1 }))
  const openCreate = () => { setEditingSupplier(undefined); setIsFormOpen(true) }
  const openEdit = (supplier: Supplier) => { setEditingSupplier(supplier); setIsFormOpen(true) }
  const save = (values: SupplierFormValues) => editingSupplier ? updateMutation.mutate({ supplier: editingSupplier, values }) : createMutation.mutate(values)

  return (
    <div className="space-y-6">
      <PageHeader title="Supplier management" description="Maintain supplier organizations used for procurement and purchase orders." actions={<Button onClick={openCreate}><Truck aria-hidden="true" size={17} /> Create supplier</Button>} />
      {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <section className="grid gap-3 rounded-xl border border-border bg-surface p-4 shadow-panel md:grid-cols-[minmax(0,1fr)_180px]">
        <label className="relative block"><span className="sr-only">Search suppliers</span><Search aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" size={18} /><input className="h-11 w-full rounded-lg border border-border bg-surface pl-10 pr-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" placeholder="Search by name or code" value={filters.search} onChange={(event: ChangeEvent<HTMLInputElement>) => updateFilter('search', event.target.value)} /></label>
        <select className="h-11 rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.active} onChange={(event) => updateFilter('active', event.target.value as SupplierFilters['active'])}>
          <option value="all">All statuses</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </section>

      <div className="flex items-center justify-between text-sm text-muted"><p>{suppliersQuery.data?.meta.total ?? 0} suppliers</p><p>{suppliersQuery.isFetching ? 'Updating…' : 'Server pagination enabled'}</p></div>
      <SupplierTable suppliers={suppliers} onEdit={openEdit} />

      <nav aria-label="Supplier pagination" className="flex items-center justify-between gap-3"><p className="text-sm text-muted">Page {filters.page} of {totalPages}</p><div className="flex gap-2"><Button disabled={filters.page <= 1} variant="secondary" onClick={() => updateFilter('page', filters.page - 1)}>Previous</Button><Button disabled={filters.page >= totalPages} variant="secondary" onClick={() => updateFilter('page', filters.page + 1)}>Next</Button></div></nav>

      {isFormOpen ? <SupplierFormDialog isSaving={createMutation.isPending || updateMutation.isPending} supplier={editingSupplier} onClose={() => { setIsFormOpen(false); setEditingSupplier(undefined) }} onSave={save} /> : null}
    </div>
  )
}
