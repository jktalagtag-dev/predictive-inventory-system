import { useQuery } from '@tanstack/react-query'
import { getUsers, getUsersPlaceholder, userQueryKeys } from '@/features/users/api/usersApi'
import type { UserFilters } from '@/features/users/types/user'

export function useUsers(filters: UserFilters) {
  return useQuery({
    queryKey: userQueryKeys.list(filters),
    queryFn: () => getUsers(filters),
    placeholderData: () => getUsersPlaceholder(filters),
    retry: false,
  })
}
