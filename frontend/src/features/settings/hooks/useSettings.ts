import { useQuery } from '@tanstack/react-query'
import { getSettings, settingsQueryKeys } from '@/features/settings/api/settingsApi'

export function useSettings(branchId: string | null) {
  return useQuery({ queryKey: settingsQueryKeys.list(branchId), queryFn: () => getSettings(branchId) })
}
