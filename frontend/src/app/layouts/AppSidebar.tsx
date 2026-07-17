import { Boxes, ClipboardList, LayoutDashboard, PackageCheck, PackageSearch, PanelLeftClose, PanelLeftOpen, Receipt, ShoppingCart, Tags, Truck, Users } from 'lucide-react'
import { NavLink } from 'react-router-dom'
import { Button } from '@/shared/components/Button'
import { cn } from '@/shared/lib/cn'
import { useUiStore } from '@/shared/state/uiStore'
import { useAuth } from '@/features/auth/AuthProvider'

const navigation = [
  { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard, permission: undefined as string | undefined },
  { label: 'Users', to: '/users', icon: Users, permission: 'users.read' },
  { label: 'Products', to: '/products', icon: Boxes, permission: 'products.read' },
  { label: 'Categories', to: '/categories', icon: Tags, permission: 'categories.read' },
  { label: 'Inventory', to: '/inventory', icon: PackageSearch, permission: 'inventory.read' },
  { label: 'Suppliers', to: '/suppliers', icon: Truck, permission: 'suppliers.read' },
  { label: 'Purchase Orders', to: '/purchase-orders', icon: ClipboardList, permission: 'purchase_orders.read' },
  { label: 'Goods Receiving', to: '/goods-receipts', icon: PackageCheck, permission: 'goods_receipts.read' },
  { label: 'Point of Sale', to: '/pos', icon: ShoppingCart, permission: 'pos.use' },
  { label: 'Sales', to: '/sales', icon: Receipt, permission: 'sales.read' },
]

export function AppSidebar() {
  const isSidebarOpen = useUiStore((state) => state.isSidebarOpen)
  const toggleSidebar = useUiStore((state) => state.toggleSidebar)
  const { hasPermission } = useAuth()
  const visibleNavigation = navigation.filter((item) => !item.permission || hasPermission(item.permission))

  return (
    <aside
      className={cn(
        'fixed bottom-0 left-0 top-16 z-20 hidden border-r border-border bg-surface transition-[width] duration-200 motion-reduce:transition-none lg:flex lg:flex-col',
        isSidebarOpen ? 'w-64' : 'w-[4.5rem]',
      )}
    >
      <nav aria-label="Primary navigation" className="flex-1 space-y-2 p-3">
        {visibleNavigation.map(({ label, to, icon: Icon }) => (
          <NavLink
            key={to}
            to={to}
            className={({ isActive }) =>
              cn(
                'flex h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium outline-none transition-colors focus-visible:ring-2 focus-visible:ring-brand-600',
                isActive ? 'bg-brand-50 text-brand-700' : 'text-muted hover:bg-subtle hover:text-ink',
              )
            }
          >
            <Icon aria-hidden="true" size={19} />
            {isSidebarOpen ? <span>{label}</span> : <span className="sr-only">{label}</span>}
          </NavLink>
        ))}
      </nav>
      <div className="border-t border-border p-3">
        <Button
          aria-label={isSidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'}
          className="w-full justify-start"
          variant="ghost"
          onClick={toggleSidebar}
        >
          {isSidebarOpen ? <PanelLeftClose aria-hidden="true" size={19} /> : <PanelLeftOpen aria-hidden="true" size={19} />}
          {isSidebarOpen ? <span className="ml-3">Collapse</span> : null}
        </Button>
      </div>
    </aside>
  )
}
