import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { AdjustmentStatusBadge } from '@/features/inventory/components/AdjustmentStatusBadge'

describe('AdjustmentStatusBadge', () => {
  it('labels an unapproved pending adjustment as pending approval', () => {
    render(<AdjustmentStatusBadge status="pending_approval" isApproved={false} />)

    expect(screen.getByText('Pending approval')).toBeInTheDocument()
  })

  it('distinguishes an approved-but-unposted adjustment from a plain pending one', () => {
    render(<AdjustmentStatusBadge status="pending_approval" isApproved />)

    expect(screen.getByText('Approved · awaiting post')).toBeInTheDocument()
    expect(screen.queryByText('Pending approval')).not.toBeInTheDocument()
  })

  it('labels a posted adjustment as posted', () => {
    render(<AdjustmentStatusBadge status="posted" />)

    expect(screen.getByText('Posted')).toBeInTheDocument()
  })
})
