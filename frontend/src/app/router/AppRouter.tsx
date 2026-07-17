import { lazy, Suspense } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AppShell } from '@/app/layouts/AppShell'
import { AuthenticationLayout } from '@/app/layouts/AuthenticationLayout'
import { RouteErrorBoundary } from '@/app/router/RouteErrorBoundary'
import { ProtectedRoute } from '@/app/router/ProtectedRoute'
import { PublicOnlyRoute } from '@/app/router/PublicOnlyRoute'
import { PageSkeleton } from '@/shared/components/PageSkeleton'

const DashboardPage = lazy(() => import('@/features/dashboard/pages/DashboardPage'))
const UsersPage = lazy(() => import('@/features/users/pages/UsersPage'))
const ProductsPage = lazy(() => import('@/features/products/pages/ProductsPage'))
const CategoriesPage = lazy(() => import('@/features/categories/pages/CategoriesPage'))
const SuppliersPage = lazy(() => import('@/features/suppliers/pages/SuppliersPage'))
const PurchaseOrdersPage = lazy(() => import('@/features/purchase-orders/pages/PurchaseOrdersPage'))
const GoodsReceiptsPage = lazy(() => import('@/features/receiving/pages/GoodsReceiptsPage'))
const InventoryPage = lazy(() => import('@/features/inventory/pages/InventoryPage'))
const PosPage = lazy(() => import('@/features/pos/pages/PosPage'))
const SalesPage = lazy(() => import('@/features/sales/pages/SalesPage'))
const ForecastingPage = lazy(() => import('@/features/forecasting/pages/ForecastingPage'))
const RestockingPage = lazy(() => import('@/features/restocking/pages/RestockingPage'))
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
                <Route path="/users" element={<UsersPage />} />
                <Route path="/products" element={<ProductsPage />} />
                <Route path="/categories" element={<CategoriesPage />} />
                <Route path="/suppliers" element={<SuppliersPage />} />
                <Route path="/purchase-orders" element={<PurchaseOrdersPage />} />
                <Route path="/goods-receipts" element={<GoodsReceiptsPage />} />
                <Route path="/inventory" element={<InventoryPage />} />
                <Route path="/pos" element={<PosPage />} />
                <Route path="/sales" element={<SalesPage />} />
                <Route path="/forecasting" element={<ForecastingPage />} />
                <Route path="/restocking" element={<RestockingPage />} />
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
