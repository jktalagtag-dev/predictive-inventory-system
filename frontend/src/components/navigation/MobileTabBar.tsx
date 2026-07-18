import type { LucideIcon } from 'lucide-react'
import { Boxes, LayoutDashboard, LineChart, MoreHorizontal, ShoppingCart } from 'lucide-react'
import { NavLink } from 'react-router-dom'

import type { AppRoutePath } from '@/components/navigation/types'
import { useAuth } from '@/features/auth/AuthProvider'
import { cn } from '@/shared/lib/cn'
import { useUiStore } from '@/shared/state/uiStore'

type TabItem = {
  key: string
  label: string
  to: AppRoutePath
  icon: LucideIcon
  permission?: string
}

const tabs: TabItem[] = [
  { key: 'dashboard', label: 'Home', to: '/dashboard', icon: LayoutDashboard },
  { key: 'stock', label: 'Stock', to: '/inventory', icon: Boxes, permission: 'inventory.read' },
  { key: 'pos', label: 'POS', to: '/pos', icon: ShoppingCart, permission: 'pos.use' },
  { key: 'reorder', label: 'Reorder', to: '/restocking', icon: LineChart, permission: 'restocking.read' },
]

const tabBaseClass =
  'flex min-h-14 flex-1 flex-col items-center justify-center gap-1 px-1 text-[0.68rem] font-medium outline-none transition-colors focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600'

/**
 * Thumb-reachable primary navigation for mobile (< lg). Surfaces the
 * highest-frequency destinations plus a "More" affordance that opens the
 * full grouped drawer (AppSidebar) as a sheet. Hidden on desktop, where
 * the persistent sidebar rail is the navigation surface.
 */
export function MobileTabBar() {
  const { hasPermission } = useAuth()
  const openMobileNav = useUiStore((state) => state.openMobileNav)

  const visibleTabs = tabs.filter((tab) => !tab.permission || hasPermission(tab.permission))

  return (
    <nav
      aria-label="Primary mobile navigation"
      className="fixed inset-x-0 bottom-0 z-40 flex border-t border-border bg-surface/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden"
    >
      {visibleTabs.map((tab) => {
        const Icon = tab.icon
        return (
          <NavLink
            key={tab.key}
            to={tab.to}
            className={({ isActive }) => cn(tabBaseClass, isActive ? 'text-brand-700' : 'text-muted hover:text-ink')}
          >
            <Icon aria-hidden="true" size={21} />
            <span>{tab.label}</span>
          </NavLink>
        )
      })}
      <button className={cn(tabBaseClass, 'text-muted hover:text-ink')} type="button" onClick={openMobileNav}>
        <MoreHorizontal aria-hidden="true" size={21} />
        <span>More</span>
      </button>
    </nav>
  )
}
