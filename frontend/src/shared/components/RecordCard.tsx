import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

export type RecordCardField = {
  label: string
  value: ReactNode
  /** Span both columns of the field grid (for long values). */
  full?: boolean
}

type RecordCardProps = {
  title: ReactNode
  subtitle?: ReactNode
  badge?: ReactNode
  fields?: RecordCardField[]
  actions?: ReactNode
  onClick?: () => void
  /** Accessible label when the whole card is tappable. */
  ariaLabel?: string
}

/**
 * Mobile presentation of a single tabular record. Feature tables render
 * a `<Table>` at `md`+ (`hidden md:block`) and a list of these cards
 * below `md` (`md:hidden`), so dense rows become a thumb-friendly,
 * no-horizontal-scroll stack instead of a side-scrolling table
 * (CLAUDE.md sections 31, 33). Standardizes the card chrome, header
 * (title/subtitle + status badge) and label/value grid so every feature
 * looks consistent; the caller chooses which columns map to which slot.
 */
export function RecordCard({ title, subtitle, badge, fields, actions, onClick, ariaLabel }: RecordCardProps) {
  const header = (
    <div className="flex items-start justify-between gap-3">
      <div className="min-w-0">
        <div className="truncate font-semibold text-ink">{title}</div>
        {subtitle ? <div className="mt-0.5 truncate text-xs text-muted">{subtitle}</div> : null}
      </div>
      {badge ? <div className="shrink-0">{badge}</div> : null}
    </div>
  )

  const body = fields && fields.length > 0 ? (
    <dl className="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
      {fields.map((field) => (
        <div key={field.label} className={cn('min-w-0', field.full && 'col-span-2')}>
          <dt className="text-[0.7rem] font-semibold uppercase tracking-wide text-muted">{field.label}</dt>
          <dd className="mt-0.5 truncate text-ink">{field.value}</dd>
        </div>
      ))}
    </dl>
  ) : null

  const cardClass = 'rounded-card border border-border bg-surface p-4 shadow-panel'

  if (onClick) {
    return (
      <div className={`${cardClass} transition-colors duration-200 motion-reduce:transition-none hover:border-brand-600/40 hover:bg-subtle`}>
        <button
          aria-label={ariaLabel}
          className="block w-full min-h-11 text-left outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
          type="button"
          onClick={onClick}
        >
          {header}
          {body}
        </button>
        {actions ? <div className="mt-3 flex flex-wrap justify-end gap-2 border-t border-border pt-3">{actions}</div> : null}
      </div>
    )
  }

  return (
    <div className={cardClass}>
      {header}
      {body}
      {actions ? <div className="mt-3 flex flex-wrap justify-end gap-2 border-t border-border pt-3">{actions}</div> : null}
    </div>
  )
}
