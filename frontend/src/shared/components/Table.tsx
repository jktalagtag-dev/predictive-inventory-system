import type { PropsWithChildren, ReactNode, TdHTMLAttributes, ThHTMLAttributes } from 'react'
import { cn } from '@/shared/lib/cn'

/**
 * Shared table chrome (card border, header shading, row dividers,
 * horizontal scroll on overflow) so every feature table looks and
 * behaves the same way (CLAUDE.md section 33). Row content composition
 * stays with each feature — this only standardizes the surrounding
 * structure and spacing, not the domain-specific cells.
 */
export function Table({ children, minWidth = 600 }: PropsWithChildren<{ minWidth?: number }>) {
  return (
    <section className="overflow-hidden rounded-card border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full text-sm" style={{ minWidth }}>
          {children}
        </table>
      </div>
    </section>
  )
}

export function TableHead({ children }: PropsWithChildren) {
  return <thead className="bg-subtle text-left text-xs font-semibold uppercase tracking-wide text-muted">{children}</thead>
}

export function TableBody({ children }: PropsWithChildren) {
  return <tbody className="divide-y divide-border">{children}</tbody>
}

export function TableRow({ children, className }: PropsWithChildren<{ className?: string }>) {
  return <tr className={cn('transition-colors duration-150 hover:bg-subtle/70', className)}>{children}</tr>
}

type AlignProp = { align?: 'left' | 'right' }

export function TableHeaderCell({ children, align = 'left', ...rest }: PropsWithChildren<AlignProp & ThHTMLAttributes<HTMLTableCellElement>>) {
  return (
    <th className={cn('px-5 py-3.5', align === 'right' && 'text-right')} {...rest}>
      {children}
    </th>
  )
}

export function TableCell({ children, align = 'left', className, ...rest }: PropsWithChildren<AlignProp & TdHTMLAttributes<HTMLTableCellElement>>) {
  return (
    <td className={cn('px-5 py-4', align === 'right' && 'text-right tabular-nums', className)} {...rest}>
      {children}
    </td>
  )
}

export function TableEmptyState({ colSpan, children }: { colSpan: number; children: ReactNode }) {
  return (
    <tr>
      <td className="px-5 py-8 text-center text-sm text-muted" colSpan={colSpan}>
        {children}
      </td>
    </tr>
  )
}
