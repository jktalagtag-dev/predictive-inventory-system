import type { AdjustmentStatus } from '@/features/inventory/types/inventory'

export type AdjustmentActions = {
  canUpdate: boolean
  canApprove: boolean
  canPost: boolean
  canReverse: boolean
}

/**
 * Mirrors the exact state-transition guards enforced server-side in
 * InventoryAdjustmentService (backend/app/Domains/Inventory/Services/
 * InventoryAdjustmentService.php): status alone does not disambiguate
 * "pending approval, not yet approved" from "approved, not yet posted" —
 * both are status 'pending_approval', distinguished only by approvedAt.
 * Kept as a pure function so the four workflow guards can be unit tested
 * without rendering a component or mocking the API (CLAUDE.md section 26).
 */
export function getAdjustmentActions(adjustment: { status: AdjustmentStatus; approvedAt: string | null }): AdjustmentActions {
  const isUnapprovedDraft = adjustment.status === 'draft' || (adjustment.status === 'pending_approval' && adjustment.approvedAt === null)

  return {
    canUpdate: isUnapprovedDraft,
    canApprove: adjustment.status === 'pending_approval' && adjustment.approvedAt === null,
    canPost: adjustment.status === 'pending_approval' && adjustment.approvedAt !== null,
    canReverse: adjustment.status === 'posted',
  }
}
