import { apiClient } from '@/shared/api/client'
import type { DashboardData } from '@/features/dashboard/types/dashboard'

type ApiEnvelope<T> = { data: T }

export const dashboardQueryKeys = {
  overview: ['dashboard', 'overview'] as const,
}

export const dashboardPlaceholder: DashboardData = {
  generatedAt: '2026-07-14T04:00:00.000Z',
  inventorySummary: {
    value: '₱1,284,500.00',
    label: 'Inventory Summary',
    detail: '1,248 active stock units',
  },
  salesToday: {
    value: '₱42,860.00',
    label: 'Sales Today',
    detail: '18 completed transactions',
  },
  lowStock: {
    value: '12',
    label: 'Low Stock',
    detail: 'Products at or below reorder point',
  },
  criticalStock: {
    value: '4',
    label: 'Critical Stock',
    detail: 'Products with immediate stockout risk',
  },
  forecastSummary: {
    value: '94.2%',
    label: 'Forecast Summary',
    detail: 'SMA coverage across eligible products',
  },
  eoqSummary: {
    value: '28',
    label: 'EOQ Summary',
    detail: 'Recommendations ready for review',
  },
  stockAlerts: [
    { id: 'stock-1', productName: '10 in. Sediment Filter Cartridge', sku: 'SHX-FLT-010', availableQuantity: '6', reorderPoint: '12', status: 'critical' },
    { id: 'stock-2', productName: 'Reverse Osmosis Membrane', sku: 'SHX-RO-4040', availableQuantity: '4', reorderPoint: '8', status: 'critical' },
    { id: 'stock-3', productName: 'Activated Carbon Media', sku: 'SHX-CAR-025', availableQuantity: '18', reorderPoint: '24', status: 'low' },
    { id: 'stock-4', productName: 'Pressure Gauge 0–160 PSI', sku: 'SHX-GAU-160', availableQuantity: '9', reorderPoint: '10', status: 'low' },
  ],
  recentActivity: [
    { id: 'activity-1', title: 'Goods receipt posted', detail: 'PO-2026-0048 · 36 accepted units', occurredAt: '8 minutes ago', type: 'receipt' },
    { id: 'activity-2', title: 'Sale completed', detail: 'SALE-2026-0137 · ₱3,450.00', occurredAt: '24 minutes ago', type: 'sale' },
    { id: 'activity-3', title: 'Forecast run completed', detail: 'Monthly SMA · 118 products evaluated', occurredAt: '1 hour ago', type: 'forecast' },
    { id: 'activity-4', title: 'Inventory adjustment posted', detail: 'ADJ-2026-0021 · Count correction approved', occurredAt: '2 hours ago', type: 'adjustment' },
  ],
}

export async function getDashboard(): Promise<DashboardData> {
  const response = await apiClient.get<ApiEnvelope<DashboardData>>('/dashboard')
  return response.data.data
}
