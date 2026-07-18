import type { FocusEvent } from 'react'
import { useEffect, useMemo, useState } from 'react'
import { useLocation } from 'react-router-dom'

import { sidebarNavigation } from '@/components/navigation/navigation'
import { SidebarFooter } from '@/components/navigation/SidebarFooter'
import { SidebarItem } from '@/components/navigation/SidebarItem'
import { SidebarSection } from '@/components/navigation/SidebarSection'
import type { NavigationGroupKey, VisibleSidebarSection } from '@/components/navigation/types'
import { useAuth } from '@/features/auth/AuthProvider'
import { cn } from '@/shared/lib/cn'
import { useUiStore } from '@/shared/state/uiStore'

export function AppSidebar() {
  const isSidebarOpen = useUiStore((state) => state.isSidebarOpen)
  const toggleSidebar = useUiStore((state) => state.toggleSidebar)
  const isSidebarHoverExpanded = useUiStore((state) => state.isSidebarHoverExpanded)
  const setSidebarHoverExpanded = useUiStore((state) => state.setSidebarHoverExpanded)
  const isMobileNavOpen = useUiStore((state) => state.isMobileNavOpen)
  const closeMobileNav = useUiStore((state) => state.closeMobileNav)

  const { hasPermission } = useAuth()
  const location = useLocation()

  const [expandedGroupKey, setExpandedGroupKey] = useState<NavigationGroupKey | null>(null)

  const isSidebarExpanded = isMobileNavOpen || isSidebarOpen || isSidebarHoverExpanded

  const visibleSections = useMemo<VisibleSidebarSection[]>(() => {
    return sidebarNavigation.sections
      .map((section) => ({
        ...section,
        groups: section.groups
          .map((group) => ({
            ...group,
            items: group.items.filter((item) => !item.permission || hasPermission(item.permission)),
          }))
          .filter((group) => group.items.length > 0),
      }))
      .filter((section) => section.groups.length > 0)
  }, [hasPermission])

  useEffect(() => {
    closeMobileNav()
  }, [location.pathname, closeMobileNav])

  useEffect(() => {
    if (!isMobileNavOpen) return

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') closeMobileNav()
    }

    document.addEventListener('keydown', onKeyDown)

    return () => document.removeEventListener('keydown', onKeyDown)
  }, [isMobileNavOpen, closeMobileNav])

  useEffect(() => {
    const activeGroup = visibleSections
      .flatMap((section) => section.groups)
      .find((group) => group.items.some((item) => location.pathname.startsWith(item.to)))

    setExpandedGroupKey(activeGroup?.key ?? null)
  }, [location.pathname, visibleSections])

  useEffect(() => {
    if (isSidebarOpen) {
      setSidebarHoverExpanded(false)
    }
  }, [isSidebarOpen, setSidebarHoverExpanded])

  const handleGroupToggle = (groupKey: NavigationGroupKey) => {
    setExpandedGroupKey((currentGroupKey) => (currentGroupKey === groupKey ? null : groupKey))
  }

  const openHoveredSidebar = () => {
    if (!isSidebarOpen && !isMobileNavOpen) {
      setSidebarHoverExpanded(true)
    }
  }

  const closeHoveredSidebar = () => {
    if (!isSidebarOpen) {
      setSidebarHoverExpanded(false)
    }
  }

  const closeHoveredSidebarOnBlur = (event: FocusEvent<HTMLElement>) => {
    if (!event.currentTarget.contains(event.relatedTarget)) {
      closeHoveredSidebar()
    }
  }

  return (
    <>
      {isMobileNavOpen ? (
        <div aria-hidden="true" className="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" onClick={closeMobileNav} />
      ) : null}

      <aside
        className={cn(
          'fixed bottom-0 left-0 top-16 z-40 flex w-[min(20rem,86vw)] flex-col bg-sidebar shadow-2xl shadow-slate-950/10 transition-transform duration-300 ease-out motion-reduce:transition-none lg:z-20 lg:translate-x-0 lg:shadow-none lg:transition-[width]',
          isMobileNavOpen ? 'translate-x-0' : '-translate-x-full',
          isSidebarExpanded ? 'lg:w-sidebar-expanded' : 'lg:w-sidebar-collapsed',
        )}
        onBlur={closeHoveredSidebarOnBlur}
        onFocus={openHoveredSidebar}
        onMouseEnter={openHoveredSidebar}
        onMouseLeave={closeHoveredSidebar}
      >
        <nav aria-label="Primary navigation" className="min-h-0 flex-1 overflow-y-auto px-3 py-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
          <SidebarItem
            ariaLabel={sidebarNavigation.dashboard.ariaLabel}
            icon={sidebarNavigation.dashboard.icon}
            isExpanded={isSidebarExpanded}
            label={sidebarNavigation.dashboard.label}
            to={sidebarNavigation.dashboard.to}
            variant="primary"
            onNavigate={closeMobileNav}
          />

          <div className="my-5 border-t border-white/10" />

          <div className="space-y-7">
            {visibleSections.map((section) => (
              <SidebarSection
                key={section.key}
                expandedGroupKey={expandedGroupKey}
                isSidebarExpanded={isSidebarExpanded}
                section={section}
                onGroupToggle={handleGroupToggle}
                onNavigate={closeMobileNav}
              />
            ))}
          </div>
        </nav>

        <SidebarFooter
          isSidebarExpanded={isSidebarExpanded}
          isSidebarOpen={isSidebarOpen}
          onToggleSidebar={toggleSidebar}
        />
      </aside>
    </>
  )
}
