import { describe, expect, it } from 'vitest'
import { computeHistoryStartDate } from '@/features/forecasting/lib/period'

describe('computeHistoryStartDate', () => {
  it('computes a daily window spanning windowPeriods days back from the end date', () => {
    expect(computeHistoryStartDate('daily', 2, '2026-07-16')).toBe('2026-07-15')
    expect(computeHistoryStartDate('daily', 7, '2026-07-16')).toBe('2026-07-10')
  })

  it('computes a weekly window using 7-day periods', () => {
    expect(computeHistoryStartDate('weekly', 2, '2026-07-16')).toBe('2026-07-03')
  })

  it('computes a monthly window using 30-day periods', () => {
    expect(computeHistoryStartDate('monthly', 2, '2026-07-16')).toBe('2026-05-18')
  })
})
