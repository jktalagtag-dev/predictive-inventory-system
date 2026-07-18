import { Outlet } from 'react-router-dom'
import { BrandMark } from '@/shared/components/BrandMark'

export function AuthenticationLayout() {
  return (
    <div className="grid min-h-screen lg:grid-cols-[3fr_2fr]">
      <aside className="hidden flex-col items-center justify-center gap-6 bg-sidebar p-10 lg:flex" aria-hidden="true">
        <BrandMark size={64} />
        <div className="text-center">
          <p className="text-3xl font-semibold text-white">Predictive Inventory System</p>
          <p className="mt-2 text-sm text-white/70">Steven Hydrotech Exponent</p>
        </div>
      </aside>
      <main className="flex items-center justify-center bg-canvas p-6 sm:p-10">
        <Outlet />
      </main>
    </div>
  )
}
