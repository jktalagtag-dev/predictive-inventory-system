import { apiClient } from '@/shared/api/client'
import type { BranchOption, ManagedUser, PaginatedUsers, RoleOption, UserFilters, UserFormValues } from '@/features/users/types/user'

type ApiEnvelope<T> = { data: T; meta?: PaginatedUsers['meta'] }

export const userQueryKeys = {
  lists: () => ['users'] as const,
  list: (filters: UserFilters) => ['users', filters] as const,
}

export const roleQueryKeys = { list: () => ['roles'] as const }
export const branchQueryKeys = { list: () => ['branches'] as const }

export async function getUsers(filters: UserFilters): Promise<PaginatedUsers> {
  const response = await apiClient.get<ApiEnvelope<ManagedUser[]>>('/users', {
    params: {
      search: filters.search || undefined,
      role: filters.roleCode === 'all' ? undefined : filters.roleCode,
      isActive: filters.status === 'all' ? undefined : filters.status === 'active',
      page: filters.page,
      perPage: filters.perPage,
      sort: 'displayName',
    },
  })

  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function createUser(values: UserFormValues): Promise<ManagedUser> {
  const response = await apiClient.post<ApiEnvelope<ManagedUser>>('/users', {
    firstName: values.firstName,
    lastName: values.lastName,
    email: values.email,
    phone: values.phone || undefined,
    roleIds: values.roleIds,
    branchIds: values.branchIds,
    defaultBranchId: values.defaultBranchId,
    isActive: values.isActive,
  })
  return response.data.data
}

export async function updateUser(user: ManagedUser, values: UserFormValues): Promise<ManagedUser> {
  const response = await apiClient.patch<ApiEnvelope<ManagedUser>>(`/users/${user.id}`, {
    firstName: values.firstName,
    lastName: values.lastName,
    email: values.email,
    phone: values.phone || undefined,
    roleIds: values.roleIds,
    branchIds: values.branchIds,
    defaultBranchId: values.defaultBranchId,
    isActive: values.isActive,
    version: user.version,
  })
  return response.data.data
}

export async function setUserActive(user: ManagedUser, isActive: boolean): Promise<ManagedUser> {
  const response = await apiClient.patch<ApiEnvelope<ManagedUser>>(`/users/${user.id}`, { isActive, version: user.version })
  return response.data.data
}

export async function getRoles(): Promise<RoleOption[]> {
  const response = await apiClient.get<ApiEnvelope<RoleOption[]>>('/roles')
  return response.data.data
}

export async function getBranches(): Promise<BranchOption[]> {
  const response = await apiClient.get<ApiEnvelope<BranchOption[]>>('/branches', { params: { isActive: true } })
  return response.data.data
}
