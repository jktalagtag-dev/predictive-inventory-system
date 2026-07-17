import { useQuery } from '@tanstack/react-query'
import {
  getInventoryAdjustment,
  getInventoryAdjustments,
  getInventoryBalances,
  getInventoryMovements,
  inventoryQueryKeys,
} from '@/features/inventory/api/inventoryApi'
import type { InventoryAdjustmentFilters, InventoryBalanceFilters, InventoryMovementFilters } from '@/features/inventory/types/inventory'

export function useInventoryBalances(filters: InventoryBalanceFilters) {
  return useQuery({ queryKey: inventoryQueryKeys.balances(filters), queryFn: () => getInventoryBalances(filters), enabled: filters.branchId !== null })
}

export function useInventoryMovements(filters: InventoryMovementFilters) {
  return useQuery({ queryKey: inventoryQueryKeys.movements(filters), queryFn: () => getInventoryMovements(filters), enabled: filters.branchId !== null })
}

export function useInventoryAdjustments(filters: InventoryAdjustmentFilters) {
  return useQuery({ queryKey: inventoryQueryKeys.adjustmentList(filters), queryFn: () => getInventoryAdjustments(filters), enabled: filters.branchId !== null })
}

export function useInventoryAdjustment(id: string | undefined) {
  return useQuery({ queryKey: inventoryQueryKeys.adjustmentDetail(id ?? ''), queryFn: () => getInventoryAdjustment(id as string), enabled: id !== undefined })
}
