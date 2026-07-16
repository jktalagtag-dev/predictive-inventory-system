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
    } catch (error) {
      // Sign-out must still be reflected locally even if the network call
      // fails, so a flaky connection can never leave the UI stuck showing
      // an authenticated screen after the user asked to sign out.
      console.error('Failed to notify the server of sign-out.', error)
    } finally {
      queryClient.clear()
      // clear() only invalidates the cache and lets the session query
      // refetch in the background; that refetch can lose the race against
      // the redirect that follows logout(). Setting the session to null
      // directly makes isAuthenticated flip to false synchronously.
      queryClient.setQueryData(authQueryKeys.session, null)
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
