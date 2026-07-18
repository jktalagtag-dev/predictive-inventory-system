export type RoleOption = {
  id: string
  code: string
  name: string
}

export type BranchOption = {
  id: string
  code: string
  name: string
}

export type UserBranchAssignment = BranchOption & { isDefault: boolean }

export type ManagedUser = {
  id: string
  firstName: string
  lastName: string
  displayName: string
  email: string
  phone: string | null
  avatarUrl: string | null
  isActive: boolean
  roles: RoleOption[]
  branches: UserBranchAssignment[]
  lastLoginAt: string | null
  version: number
}

export type UserFilters = {
  search: string
  roleCode: string | 'all'
  status: 'all' | 'active' | 'inactive'
  page: number
  perPage: number
}

export type UserFormValues = {
  firstName: string
  lastName: string
  email: string
  phone: string
  roleIds: string[]
  branchIds: string[]
  defaultBranchId: string
  isActive: boolean
}

export type PaginatedUsers = {
  data: ManagedUser[]
  meta: {
    page: number
    perPage: number
    total: number
  }
}
