import { useQuery } from '@tanstack/react-query'
import {
  getEoqHistory,
  getReorderPolicies,
  getRestockingAlert,
  getRestockingAlerts,
  reorderPolicyQueryKeys,
  restockingAlertQueryKeys,
} from '@/features/restocking/api/restockingApi'
import type { ReorderPolicyFilters, RestockingAlertFilters } from '@/features/restocking/types/restocking'

export function useReorderPolicies(filters: ReorderPolicyFilters) {
  return useQuery({ queryKey: reorderPolicyQueryKeys.list(filters), queryFn: () => getReorderPolicies(filters), enabled: filters.branchId !== null })
}

export function useEoqHistory(policyId: string | undefined) {
  return useQuery({ queryKey: reorderPolicyQueryKeys.eoqHistory(policyId ?? ''), queryFn: () => getEoqHistory(policyId as string), enabled: policyId !== undefined })
}

export function useRestockingAlerts(filters: RestockingAlertFilters) {
  return useQuery({ queryKey: restockingAlertQueryKeys.list(filters), queryFn: () => getRestockingAlerts(filters), enabled: filters.branchId !== null })
}

export function useRestockingAlert(id: string | undefined) {
  return useQuery({ queryKey: restockingAlertQueryKeys.detail(id ?? ''), queryFn: () => getRestockingAlert(id as string), enabled: id !== undefined })
}
