import type { LucideIcon } from 'lucide-react'

export type NavigationPermission = string

export type AppRoutePath =
  | '/dashboard'
  | '/products'
  | '/categories'
  | '/inventory'
  | '/pos'
  | '/sales'
  | '/suppliers'
  | '/purchase-orders'
  | '/goods-receipts'
  | '/forecasting'
  | '/restocking'
  | '/reports'
  | '/audit'
  | '/users'
  | '/settings'
  | '/sync'

export type NavigationSectionKey = 'operations' | 'intelligence' | 'system'

export type NavigationGroupKey =
  | 'inventory'
  | 'sales'
  | 'purchasing'
  | 'demand-planning'
  | 'analytics'
  | 'administration'

export interface SidebarBaseItem {
  key: string
  label: string
  ariaLabel?: string
}

export interface SidebarDashboardItem extends SidebarBaseItem {
  kind: 'dashboard'
  to: AppRoutePath
  icon: LucideIcon
  permission?: NavigationPermission
}

export interface SidebarChildItem extends SidebarBaseItem {
  to: AppRoutePath
  permission?: NavigationPermission
}

export interface SidebarGroup extends SidebarBaseItem {
  kind: 'group'
  key: NavigationGroupKey
  icon: LucideIcon
  items: readonly SidebarChildItem[]
}

export interface SidebarSection {
  key: NavigationSectionKey
  label: string
  groups: readonly SidebarGroup[]
}

export interface SidebarNavigationConfig {
  dashboard: SidebarDashboardItem
  sections: readonly SidebarSection[]
}

export interface VisibleSidebarGroup extends Omit<SidebarGroup, 'items'> {
  items: SidebarChildItem[]
}

export interface VisibleSidebarSection extends Omit<SidebarSection, 'groups'> {
  groups: VisibleSidebarGroup[]
}
