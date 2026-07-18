export type BranchAccess = {
  id: string
  code: string
  name: string
  isDefault: boolean
}

export type AuthenticatedUser = {
  id: string
  firstName: string
  lastName: string
  displayName: string
  email: string
  avatarUrl: string | null
  roles: string[]
  permissions: string[]
  branches: BranchAccess[]
}

export type AuthSession = {
  user: AuthenticatedUser
  expiresAt: string | null
}

export type LoginCredentials = {
  email: string
  password: string
  remember: boolean
}
