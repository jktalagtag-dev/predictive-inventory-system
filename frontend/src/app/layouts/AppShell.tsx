import { Outlet } from 'react-router-dom'
import { AppHeader } from '@/app/layouts/AppHeader'
import { AppSidebar } from '@/components/navigation/AppSidebar'
import { MobileTabBar } from '@/components/navigation/MobileTabBar'
import { useUiStore } from '@/shared/state/uiStore'

export function AppShell() {
  const isSidebarOpen = useUiStore((state) => state.isSidebarOpen)
  const isSidebarHoverExpanded = useUiStore((state) => state.isSidebarHoverExpanded)
  const isSidebarExpanded = isSidebarOpen || isSidebarHoverExpanded

  return (
    <div className="min-h-dvh bg-canvas text-ink">
      <AppHeader />
      <AppSidebar />
      <main
        className={`pt-16 transition-[padding-left] duration-300 ease-out motion-reduce:transition-none ${
          isSidebarExpanded ? 'lg:pl-sidebar-expanded' : 'lg:pl-sidebar-collapsed'
        }`}
      >
        <div className="mx-auto w-full max-w-[1600px] p-4 pb-[calc(4rem+env(safe-area-inset-bottom))] sm:p-6 sm:pb-[calc(4rem+env(safe-area-inset-bottom))] lg:p-8 lg:pb-8">
          <Outlet />
        </div>
      </main>
      <MobileTabBar />
    </div>
  )
}
