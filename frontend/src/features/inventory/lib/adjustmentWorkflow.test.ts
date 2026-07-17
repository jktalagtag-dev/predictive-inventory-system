import { describe, expect, it } from 'vitest'
import { getAdjustmentActions } from '@/features/inventory/lib/adjustmentWorkflow'

describe('getAdjustmentActions', () => {
  it('allows update and approve, but not post or reverse, for an unapproved pending adjustment', () => {
    const actions = getAdjustmentActions({ status: 'pending_approval', approvedAt: null })

    expect(actions).toEqual({ canUpdate: true, canApprove: true, canPost: false, canReverse: false })
  })

  it('allows post, but not update or approve, once approved and still pending', () => {
    const actions = getAdjustmentActions({ status: 'pending_approval', approvedAt: '2026-07-17T10:00:00Z' })

    expect(actions).toEqual({ canUpdate: false, canApprove: false, canPost: true, canReverse: false })
  })

  it('allows only reverse once posted', () => {
    const actions = getAdjustmentActions({ status: 'posted', approvedAt: '2026-07-17T10:00:00Z' })

    expect(actions).toEqual({ canUpdate: false, canApprove: false, canPost: false, canReverse: true })
  })

  it('allows no further actions once reversed', () => {
    const actions = getAdjustmentActions({ status: 'reversed', approvedAt: '2026-07-17T10:00:00Z' })

    expect(actions).toEqual({ canUpdate: false, canApprove: false, canPost: false, canReverse: false })
  })

  it('allows no further actions once rejected', () => {
    const actions = getAdjustmentActions({ status: 'rejected', approvedAt: null })

    expect(actions).toEqual({ canUpdate: false, canApprove: false, canPost: false, canReverse: false })
  })

  it('treats the theoretical draft status the same as an unapproved pending adjustment', () => {
    const actions = getAdjustmentActions({ status: 'draft', approvedAt: null })

    expect(actions).toEqual({ canUpdate: true, canApprove: false, canPost: false, canReverse: false })
  })
})
