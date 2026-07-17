import { apiClient } from '@/shared/api/client'
import type { DashboardResponse } from '@/features/dashboard/types/dashboard'

export const dashboardQueryKeys = {
  overview: (branchId: string) => ['dashboard', 'overview', branchId] as const,
}

export async function getDashboard(branchId: string): Promise<DashboardResponse> {
  const response = await apiClient.get<DashboardResponse>('/dashboard', { params: { branchId } })
  return response.data
}
