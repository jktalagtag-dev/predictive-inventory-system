import { useQuery } from '@tanstack/react-query'
import { branchQueryKeys, getBranches, getRoles, getUsers, roleQueryKeys, userQueryKeys } from '@/features/users/api/usersApi'
import type { UserFilters } from '@/features/users/types/user'

export function useUsers(filters: UserFilters) {
  return useQuery({ queryKey: userQueryKeys.list(filters), queryFn: () => getUsers(filters) })
}

export function useRoles() {
  return useQuery({ queryKey: roleQueryKeys.list(), queryFn: getRoles, staleTime: 5 * 60_000 })
}

export function useBranches() {
  return useQuery({ queryKey: branchQueryKeys.list(), queryFn: getBranches, staleTime: 5 * 60_000 })
}
