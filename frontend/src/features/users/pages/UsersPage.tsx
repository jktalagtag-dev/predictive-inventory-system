import { type ChangeEvent, useMemo, useState } from 'react'
import { Search, UserPlus } from 'lucide-react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { createUser, setUserActive, updateUser, userQueryKeys } from '@/features/users/api/usersApi'
import { UserFormDialog } from '@/features/users/components/UserFormDialog'
import { UserTable } from '@/features/users/components/UserTable'
import { useBranches, useRoles, useUsers } from '@/features/users/hooks/useUsers'
import type { ManagedUser, UserFilters, UserFormValues } from '@/features/users/types/user'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'
import { PageHeader } from '@/shared/components/PageHeader'

const defaultFilters: UserFilters = { search: '', roleCode: 'all', status: 'all', page: 1, perPage: 10 }

export default function UsersPage() {
  const [filters, setFilters] = useState<UserFilters>(defaultFilters)
  const [editingUser, setEditingUser] = useState<ManagedUser | undefined>()
  const [isFormOpen, setIsFormOpen] = useState(false)
  const queryClient = useQueryClient()
  const usersQuery = useUsers(filters)
  const rolesQuery = useRoles()
  const branchesQuery = useBranches()

  const invalidateUsers = () => queryClient.invalidateQueries({ queryKey: userQueryKeys.lists() })
  const createMutation = useMutation({ mutationFn: createUser, onSuccess: () => { void invalidateUsers(); setIsFormOpen(false) } })
  const updateMutation = useMutation({ mutationFn: ({ user, values }: { user: ManagedUser; values: UserFormValues }) => updateUser(user, values), onSuccess: () => { void invalidateUsers(); setIsFormOpen(false); setEditingUser(undefined) } })
  const statusMutation = useMutation({ mutationFn: ({ user, isActive }: { user: ManagedUser; isActive: boolean }) => setUserActive(user, isActive), onSuccess: invalidateUsers })

  const totalPages = Math.max(1, Math.ceil((usersQuery.data?.meta.total ?? 0) / filters.perPage))
  const error = (createMutation.error ?? updateMutation.error ?? statusMutation.error ?? usersQuery.error) as ApiError | null
  const users = usersQuery.data?.data ?? []
  const roleOptions = rolesQuery.data ?? []
  const branchOptions = branchesQuery.data ?? []
  const resultLabel = useMemo(() => `${usersQuery.data?.meta.total ?? 0} user${(usersQuery.data?.meta.total ?? 0) === 1 ? '' : 's'}`, [usersQuery.data?.meta.total])

  const updateFilter = <K extends keyof UserFilters>(key: K, value: UserFilters[K]) => setFilters((state) => ({ ...state, [key]: value, page: key === 'page' ? Number(value) : 1 }))
  const openCreate = () => { setEditingUser(undefined); setIsFormOpen(true) }
  const openEdit = (user: ManagedUser) => { setEditingUser(user); setIsFormOpen(true) }
  const saveUser = (values: UserFormValues) => editingUser ? updateMutation.mutate({ user: editingUser, values }) : createMutation.mutate(values)

  return (
    <div className="space-y-6">
      <PageHeader title="User management" description="Manage access, roles, account status, and operational responsibility." actions={<Button onClick={openCreate}><UserPlus aria-hidden="true" size={17} /> Create user</Button>} />
      {error ? <div className="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text" role="alert">{error.message}{error.requestId ? ` Request ID: ${error.requestId}` : ''}</div> : null}

      <section className="grid gap-3 rounded-card border border-border bg-surface p-4 shadow-panel sm:p-6 md:grid-cols-[minmax(0,1fr)_180px_160px]">
        <label className="relative block"><span className="sr-only">Search users</span><Search aria-hidden="true" className="absolute left-3 top-1/2 -translate-y-1/2 text-muted" size={18} /><input className="h-11 w-full rounded-xl border border-border bg-surface pl-10 pr-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" placeholder="Search by name or email" value={filters.search} onChange={(event: ChangeEvent<HTMLInputElement>) => updateFilter('search', event.target.value)} /></label>
        <label><span className="sr-only">Filter by role</span><select className="h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.roleCode} onChange={(event) => updateFilter('roleCode', event.target.value)}><option value="all">All roles</option>{roleOptions.map((role) => <option key={role.id} value={role.code}>{role.name}</option>)}</select></label>
        <label><span className="sr-only">Filter by status</span><select className="h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={filters.status} onChange={(event) => updateFilter('status', event.target.value as UserFilters['status'])}><option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
      </section>

      <div className="flex items-center justify-between text-sm text-muted"><p>{resultLabel}</p><p>{usersQuery.isFetching ? 'Updating…' : 'Server pagination enabled'}</p></div>
      <UserTable users={users} onEdit={openEdit} onStatusChange={(user) => statusMutation.mutate({ user, isActive: !user.isActive })} />

      <nav aria-label="User pagination" className="flex items-center justify-between gap-3"><p className="text-sm text-muted">Page {filters.page} of {totalPages}</p><div className="flex gap-2"><Button disabled={filters.page <= 1} variant="secondary" onClick={() => updateFilter('page', filters.page - 1)}>Previous</Button><Button disabled={filters.page >= totalPages} variant="secondary" onClick={() => updateFilter('page', filters.page + 1)}>Next</Button></div></nav>

      {isFormOpen ? <UserFormDialog branchOptions={branchOptions} isSaving={createMutation.isPending || updateMutation.isPending} roleOptions={roleOptions} user={editingUser} onClose={() => { setIsFormOpen(false); setEditingUser(undefined) }} onSave={saveUser} /> : null}
    </div>
  )
}
