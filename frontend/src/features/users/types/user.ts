export type UserRole = 'Owner' | 'Manager' | 'Staff'
export type UserStatus = 'active' | 'inactive'

export type ManagedUser = {
  id: string
  firstName: string
  lastName: string
  displayName: string
  email: string
  roles: UserRole[]
  branches: string[]
  status: UserStatus
  lastLoginAt: string | null
  version: number
}

export type UserFilters = {
  search: string
  role: UserRole | 'all'
  status: UserStatus | 'all'
  page: number
  perPage: number
}

export type UserFormValues = {
  firstName: string
  lastName: string
  email: string
  roles: UserRole[]
  status: UserStatus
}

export type PaginatedUsers = {
  data: ManagedUser[]
  meta: {
    page: number
    perPage: number
    total: number
  }
}
