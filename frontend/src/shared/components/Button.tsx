import type { ButtonHTMLAttributes, PropsWithChildren } from 'react'
import { cloneElement, isValidElement } from 'react'
import { cn } from '@/shared/lib/cn'

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  size?: 'default' | 'icon'
  asChild?: boolean
}

const variants = {
  primary: 'bg-brand-600 text-white hover:bg-brand-700 focus-visible:ring-brand-600',
  secondary: 'border border-border bg-surface text-ink hover:bg-subtle focus-visible:ring-brand-600',
  ghost: 'text-muted hover:bg-subtle hover:text-ink focus-visible:ring-brand-600',
  danger: 'bg-danger text-white hover:bg-[#B91C1C] focus-visible:ring-danger',
}

export function Button({
  children,
  className,
  variant = 'primary',
  size = 'default',
  asChild = false,
  ...props
}: PropsWithChildren<ButtonProps>) {
  const classes = cn(
    // Cursor is intentionally not set here: the base `button:not(:disabled)` rule
    // in index.css owns it, so disabled buttons correctly keep the default arrow.
    'inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold outline-none transition-colors motion-reduce:transition-none disabled:pointer-events-none disabled:opacity-50 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas',
    size === 'icon' && 'h-11 w-11 min-h-11 px-0',
    variants[variant],
    className,
  )

  if (asChild && isValidElement<{ className?: string }>(children)) {
    return cloneElement(children, { className: cn(classes, children.props.className) })
  }

  return <button className={classes} type="button" {...props}>{children}</button>
}
