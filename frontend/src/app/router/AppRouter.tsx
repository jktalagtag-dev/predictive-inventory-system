import { lazy, Suspense } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AppShell } from '@/app/layouts/AppShell'
import { AuthenticationLayout } from '@/app/layouts/AuthenticationLayout'
import { RouteErrorBoundary } from '@/app/router/RouteErrorBoundary'
import { PageSkeleton } from '@/shared/components/PageSkeleton'

const WorkspacePlaceholder = lazy(() => import('@/app/router/WorkspacePlaceholder'))
const SignInPlaceholder = lazy(() => import('@/app/router/SignInPlaceholder'))
const NotFoundPage = lazy(() => import('@/app/router/NotFoundPage'))

export function AppRouter() {
  return (
    <BrowserRouter>
      <RouteErrorBoundary>
        <Suspense fallback={<PageSkeleton />}>
          <Routes>
            <Route element={<AuthenticationLayout />}>
              <Route path="/login" element={<SignInPlaceholder />} />
            </Route>
            <Route element={<AppShell />}>
              <Route path="/workspace" element={<WorkspacePlaceholder />} />
            </Route>
            <Route path="/" element={<Navigate to="/workspace" replace />} />
            <Route path="*" element={<NotFoundPage />} />
          </Routes>
        </Suspense>
      </RouteErrorBoundary>
    </BrowserRouter>
  )
}
