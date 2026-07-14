import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '@/features/auth/AuthProvider'
import { PageSkeleton } from '@/shared/components/PageSkeleton'

export function PublicOnlyRoute() {
  const { isAuthenticated, isLoading } = useAuth()

  if (isLoading) {
    return <PageSkeleton />
  }

  return isAuthenticated ? <Navigate replace to="/workspace" /> : <Outlet />
}
