import type { PropsWithChildren, ReactNode } from 'react'
import { cn } from '@/shared/lib/cn'

export type BadgeTone = 'neutral' | 'info' | 'success' | 'warning' | 'danger'

const toneClasses: Record<BadgeTone, string> = {
  neutral: 'bg-slate-100 text-slate-700',
  info: 'bg-blue-50 text-blue-700',
  success: 'bg-emerald-50 text-emerald-700',
  warning: 'bg-amber-50 text-amber-700',
  danger: 'bg-red-50 text-red-700',
}

type BadgeProps = {
  tone?: BadgeTone
  icon?: ReactNode
  className?: string
}

/**
 * Semantic status label. Never the only signal for a status — CLAUDE.md
 * section 28 requires status to be paired with text, which this
 * enforces structurally since children is the visible label, not just
 * a color swatch.
 */
export function Badge({ tone = 'neutral', icon, className, children }: PropsWithChildren<BadgeProps>) {
  return (
    <span className={cn('inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold', toneClasses[tone], className)}>
      {icon}
      {children}
    </span>
  )
}
