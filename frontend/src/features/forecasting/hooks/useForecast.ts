import { useQuery } from '@tanstack/react-query'
import { forecastQueryKeys, getForecastRun, getForecastRuns } from '@/features/forecasting/api/forecastApi'
import type { ForecastRunFilters } from '@/features/forecasting/types/forecast'

export function useForecastRuns(filters: ForecastRunFilters) {
  return useQuery({ queryKey: forecastQueryKeys.list(filters), queryFn: () => getForecastRuns(filters), enabled: filters.branchId !== null })
}

export function useForecastRun(id: string | undefined) {
  return useQuery({ queryKey: forecastQueryKeys.detail(id ?? ''), queryFn: () => getForecastRun(id as string), enabled: id !== undefined })
}
