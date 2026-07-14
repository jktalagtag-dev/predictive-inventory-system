import { apiClient } from '@/shared/api/client'
import type { ManagedUser, PaginatedUsers, UserFilters, UserFormValues, UserStatus } from '@/features/users/types/user'

type ApiEnvelope<T> = { data: T; meta?: PaginatedUsers['meta'] }

export const userQueryKeys = {
  lists: () => ['users'] as const,
  list: (filters: UserFilters) => ['users', filters] as const,
}

const previewUsers: ManagedUser[] = [
  { id: 'usr-001', firstName: 'Maria', lastName: 'Santos', displayName: 'Maria Santos', email: 'maria.santos@stevenhydrotech.example', roles: ['Owner'], branches: ['Main Branch'], status: 'active', lastLoginAt: '2026-07-14T02:40:00.000Z', version: 1 },
  { id: 'usr-002', firstName: 'Adrian', lastName: 'Reyes', displayName: 'Adrian Reyes', email: 'adrian.reyes@stevenhydrotech.example', roles: ['Manager'], branches: ['Main Branch', 'North Branch'], status: 'active', lastLoginAt: '2026-07-14T00:10:00.000Z', version: 1 },
  { id: 'usr-003', firstName: 'Lea', lastName: 'Cruz', displayName: 'Lea Cruz', email: 'lea.cruz@stevenhydrotech.example', roles: ['Staff'], branches: ['Main Branch'], status: 'active', lastLoginAt: '2026-07-13T06:45:00.000Z', version: 1 },
  { id: 'usr-004', firstName: 'Noel', lastName: 'Garcia', displayName: 'Noel Garcia', email: 'noel.garcia@stevenhydrotech.example', roles: ['Staff'], branches: ['North Branch'], status: 'inactive', lastLoginAt: '2026-06-28T04:25:00.000Z', version: 3 },
  { id: 'usr-005', firstName: 'Patricia', lastName: 'Lim', displayName: 'Patricia Lim', email: 'patricia.lim@stevenhydrotech.example', roles: ['Manager'], branches: ['Main Branch'], status: 'active', lastLoginAt: null, version: 1 },
]

function filteredPreviewUsers(filters: UserFilters): PaginatedUsers {
  const normalizedSearch = filters.search.trim().toLowerCase()
  const filtered = previewUsers.filter((user) => {
    const matchesSearch = normalizedSearch.length === 0
      || [user.displayName, user.email].some((value) => value.toLowerCase().includes(normalizedSearch))
    const matchesRole = filters.role === 'all' || user.roles.includes(filters.role)
    const matchesStatus = filters.status === 'all' || user.status === filters.status

    return matchesSearch && matchesRole && matchesStatus
  })
  const start = (filters.page - 1) * filters.perPage

  return {
    data: filtered.slice(start, start + filters.perPage),
    meta: { page: filters.page, perPage: filters.perPage, total: filtered.length },
  }
}

export function getUsersPlaceholder(filters: UserFilters): PaginatedUsers {
  return filteredPreviewUsers(filters)
}

export async function getUsers(filters: UserFilters): Promise<PaginatedUsers> {
  const response = await apiClient.get<ApiEnvelope<ManagedUser[]>>('/users', {
    params: {
      search: filters.search || undefined,
      role: filters.role === 'all' ? undefined : filters.role,
      isActive: filters.status === 'all' ? undefined : filters.status === 'active',
      page: filters.page,
      perPage: filters.perPage,
      sort: 'displayName',
    },
  })

  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function createUser(values: UserFormValues): Promise<ManagedUser> {
  const response = await apiClient.post<ApiEnvelope<ManagedUser>>('/users', values)
  return response.data.data
}

export async function updateUser(user: ManagedUser, values: UserFormValues): Promise<ManagedUser> {
  const response = await apiClient.patch<ApiEnvelope<ManagedUser>>(`/users/${user.id}`, { ...values, version: user.version })
  return response.data.data
}

export async function setUserStatus(user: ManagedUser, status: UserStatus): Promise<ManagedUser> {
  const response = await apiClient.patch<ApiEnvelope<ManagedUser>>(`/users/${user.id}`, { status, version: user.version })
  return response.data.data
}
