import { lazy, Suspense } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AppShell } from '@/app/layouts/AppShell'
import { AuthenticationLayout } from '@/app/layouts/AuthenticationLayout'
import { RouteErrorBoundary } from '@/app/router/RouteErrorBoundary'
import { ProtectedRoute } from '@/app/router/ProtectedRoute'
import { PublicOnlyRoute } from '@/app/router/PublicOnlyRoute'
import { PageSkeleton } from '@/shared/components/PageSkeleton'

const DashboardPage = lazy(() => import('@/features/dashboard/pages/DashboardPage'))
const LoginPage = lazy(() => import('@/features/auth/pages/LoginPage'))
const NotFoundPage = lazy(() => import('@/app/router/NotFoundPage'))

export function AppRouter() {
  return (
    <BrowserRouter>
      <RouteErrorBoundary>
        <Suspense fallback={<PageSkeleton />}>
          <Routes>
            <Route element={<PublicOnlyRoute />}>
              <Route element={<AuthenticationLayout />}>
                <Route path="/login" element={<LoginPage />} />
              </Route>
            </Route>
            <Route element={<ProtectedRoute />}>
              <Route element={<AppShell />}>
                <Route path="/dashboard" element={<DashboardPage />} />
              </Route>
            </Route>
            <Route path="/workspace" element={<Navigate to="/dashboard" replace />} />
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route path="*" element={<NotFoundPage />} />
          </Routes>
        </Suspense>
      </RouteErrorBoundary>
    </BrowserRouter>
  )
}
