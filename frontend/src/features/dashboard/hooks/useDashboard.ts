import { useQuery } from '@tanstack/react-query'
import { dashboardQueryKeys, getDashboard } from '@/features/dashboard/api/dashboardApi'

export function useDashboard(branchId: string | undefined) {
  return useQuery({
    queryKey: dashboardQueryKeys.overview(branchId ?? ''),
    queryFn: () => getDashboard(branchId as string),
    enabled: branchId !== undefined,
    refetchOnWindowFocus: true,
  })
}
