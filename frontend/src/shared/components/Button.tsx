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
  danger: 'bg-red-700 text-white hover:bg-red-800 focus-visible:ring-red-700',
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
    'inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold outline-none transition-colors disabled:pointer-events-none disabled:opacity-50 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas',
    size === 'icon' && 'h-10 w-10 min-h-10 px-0',
    variants[variant],
    className,
  )

  if (asChild && isValidElement<{ className?: string }>(children)) {
    return cloneElement(children, { className: cn(classes, children.props.className) })
  }

  return <button className={classes} type="button" {...props}>{children}</button>
}
