import { describe, expect, it } from 'vitest'
import { formatRole } from '@/features/auth/lib/formatRole'

describe('formatRole', () => {
  it('capitalizes a lowercase role code', () => {
    expect(formatRole('owner')).toBe('Owner')
    expect(formatRole('manager')).toBe('Manager')
    expect(formatRole('staff')).toBe('Staff')
  })

  it('returns an empty string when no role is given', () => {
    expect(formatRole(undefined)).toBe('')
  })
})
