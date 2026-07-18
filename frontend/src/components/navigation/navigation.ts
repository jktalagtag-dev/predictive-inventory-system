import {
  BarChart3,
  Boxes,
  ClipboardList,
  LayoutDashboard,
  LineChart,
  Settings,
  ShoppingCart,
} from 'lucide-react'

import type { SidebarNavigationConfig } from '@/components/navigation/types'

export const sidebarNavigation: SidebarNavigationConfig = {
  dashboard: {
    kind: 'dashboard',
    key: 'dashboard',
    label: 'Dashboard',
    ariaLabel: 'Go to dashboard',
    to: '/dashboard',
    icon: LayoutDashboard,
  },
  sections: [
    {
      key: 'operations',
      label: 'Operations',
      groups: [
        {
          kind: 'group',
          key: 'inventory',
          label: 'Inventory',
          ariaLabel: 'Toggle inventory navigation',
          icon: Boxes,
          items: [
            {
              key: 'products',
              label: 'Products',
              to: '/products',
              permission: 'products.read',
            },
            {
              key: 'categories',
              label: 'Categories',
              to: '/categories',
              permission: 'categories.read',
            },
            {
              key: 'stock',
              label: 'Stock',
              to: '/inventory',
              permission: 'inventory.read',
            },
          ],
        },
        {
          kind: 'group',
          key: 'sales',
          label: 'Sales',
          ariaLabel: 'Toggle sales navigation',
          icon: ShoppingCart,
          items: [
            {
              key: 'point-of-sale',
              label: 'Point of Sale',
              to: '/pos',
              permission: 'pos.use',
            },
            {
              key: 'sales-history',
              label: 'Sales History',
              to: '/sales',
              permission: 'sales.read',
            },
          ],
        },
        {
          kind: 'group',
          key: 'purchasing',
          label: 'Purchasing',
          ariaLabel: 'Toggle purchasing navigation',
          icon: ClipboardList,
          items: [
            {
              key: 'suppliers',
              label: 'Suppliers',
              to: '/suppliers',
              permission: 'suppliers.read',
            },
            {
              key: 'purchase-orders',
              label: 'Purchase Orders',
              to: '/purchase-orders',
              permission: 'purchase_orders.read',
            },
            {
              key: 'goods-receiving',
              label: 'Goods Receiving',
              to: '/goods-receipts',
              permission: 'goods_receipts.read',
            },
          ],
        },
      ],
    },
    {
      key: 'intelligence',
      label: 'Intelligence',
      groups: [
        {
          kind: 'group',
          key: 'demand-planning',
          label: 'Demand Planning',
          ariaLabel: 'Toggle demand planning navigation',
          icon: LineChart,
          items: [
            {
              key: 'demand-forecast',
              label: 'Demand Forecast',
              to: '/forecasting',
              permission: 'forecasting.read',
            },
            {
              key: 'reorder-planning',
              label: 'Reorder Planning',
              to: '/restocking',
              permission: 'restocking.read',
            },
          ],
        },
        {
          kind: 'group',
          key: 'analytics',
          label: 'Analytics',
          ariaLabel: 'Toggle analytics navigation',
          icon: BarChart3,
          items: [
            {
              key: 'reports',
              label: 'Reports',
              to: '/reports',
              permission: 'reports.read',
            },
            {
              key: 'activity-logs',
              label: 'Activity Logs',
              to: '/audit',
              permission: 'audit.read',
            },
          ],
        },
      ],
    },
    {
      key: 'system',
      label: 'System',
      groups: [
        {
          kind: 'group',
          key: 'administration',
          label: 'Administration',
          ariaLabel: 'Toggle administration navigation',
          icon: Settings,
          items: [
            {
              key: 'users',
              label: 'Users',
              to: '/users',
              permission: 'users.read',
            },
            {
              key: 'settings',
              label: 'Settings',
              to: '/settings',
              permission: 'settings.read',
            },
            {
              key: 'sync-queue',
              label: 'Sync Queue',
              to: '/sync',
              permission: 'sync.use',
            },
          ],
        },
      ],
    },
  ],
} as const
