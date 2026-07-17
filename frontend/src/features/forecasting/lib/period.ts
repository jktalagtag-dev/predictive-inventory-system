import type { PeriodGrain } from '@/features/forecasting/types/forecast'

export const PERIOD_LENGTH_DAYS: Record<PeriodGrain, number> = { daily: 1, weekly: 7, monthly: 30 }

function formatLocalDate(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

/**
 * Mirrors SmaForecastService's period-boundary convention: the history
 * range must contain exactly windowPeriods complete periods counting
 * forward from the computed start date, so the start date is always
 * derived from the end date rather than picked independently.
 *
 * Deliberately does all arithmetic and formatting using UTC fields
 * (Date.UTC / getUTC*) rather than mixing local-time parsing with
 * toISOString() serialization — that combination silently shifts the
 * result by a day in any timezone with a non-zero UTC offset, since
 * toISOString() always reports the UTC calendar date.
 */
export function computeHistoryStartDate(periodGrain: PeriodGrain, windowPeriods: number, historyEndDate: string): string {
  const totalDays = windowPeriods * PERIOD_LENGTH_DAYS[periodGrain]
  const [year, month, day] = historyEndDate.split('-').map(Number)
  const start = new Date(Date.UTC(year, month - 1, day))
  start.setUTCDate(start.getUTCDate() - (totalDays - 1))

  const startYear = start.getUTCFullYear()
  const startMonth = String(start.getUTCMonth() + 1).padStart(2, '0')
  const startDay = String(start.getUTCDate()).padStart(2, '0')
  return `${startYear}-${startMonth}-${startDay}`
}

/** Yesterday in the browser's local calendar — matches an HTML date input's local-date semantics. */
export function defaultHistoryEndDate(): string {
  const yesterday = new Date()
  yesterday.setDate(yesterday.getDate() - 1)
  return formatLocalDate(yesterday)
}
