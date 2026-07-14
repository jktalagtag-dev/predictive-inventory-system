import { useQuery } from '@tanstack/react-query'
import { dashboardPlaceholder, dashboardQueryKeys, getDashboard } from '@/features/dashboard/api/dashboardApi'

export function useDashboard() {
  return useQuery({
    queryKey: dashboardQueryKeys.overview,
    queryFn: getDashboard,
    placeholderData: dashboardPlaceholder,
    retry: false,
    refetchOnWindowFocus: true,
  })
}
