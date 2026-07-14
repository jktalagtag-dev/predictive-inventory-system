import { Outlet } from 'react-router-dom'
import { AppHeader } from '@/app/layouts/AppHeader'
import { AppSidebar } from '@/app/layouts/AppSidebar'
import { useUiStore } from '@/shared/state/uiStore'

export function AppShell() {
  const isSidebarOpen = useUiStore((state) => state.isSidebarOpen)

  return (
    <div className="min-h-screen bg-canvas text-ink">
      <AppHeader />
      <AppSidebar />
      <main
        className={`pt-16 transition-[padding] duration-200 motion-reduce:transition-none ${
          isSidebarOpen ? 'lg:pl-64' : 'lg:pl-[4.5rem]'
        }`}
      >
        <div className="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
