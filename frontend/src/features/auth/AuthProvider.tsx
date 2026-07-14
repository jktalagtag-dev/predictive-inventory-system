import type { PropsWithChildren } from 'react'
import { createContext, useCallback, useContext, useMemo } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { authQueryKeys, getCurrentSession, signOut } from '@/features/auth/api/authApi'
import type { AuthSession } from '@/features/auth/types/auth'

type AuthContextValue = {
  session: AuthSession | null
  isLoading: boolean
  isAuthenticated: boolean
  hasPermission: (permission: string) => boolean
  refreshSession: () => Promise<unknown>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: PropsWithChildren) {
  const queryClient = useQueryClient()
  const sessionQuery = useQuery({
    queryKey: authQueryKeys.session,
    queryFn: getCurrentSession,
    retry: false,
    staleTime: 60_000,
    refetchOnWindowFocus: true,
  })

  const logout = useCallback(async () => {
    try {
      await signOut()
    } finally {
      queryClient.clear()
    }
  }, [queryClient])

  const value = useMemo<AuthContextValue>(() => {
    const session = sessionQuery.data ?? null

    return {
      session,
      isLoading: sessionQuery.isLoading,
      isAuthenticated: session !== null && !sessionQuery.isError,
      hasPermission: (permission) => session?.user.permissions.includes(permission) ?? false,
      refreshSession: () => sessionQuery.refetch(),
      logout,
    }
  }, [logout, sessionQuery.data, sessionQuery.isError, sessionQuery.isLoading, sessionQuery.refetch])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used within AuthProvider')
  }

  return context
}
