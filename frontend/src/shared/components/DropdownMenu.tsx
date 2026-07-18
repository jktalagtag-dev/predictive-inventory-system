import { type ReactNode, useEffect, useRef, useState } from 'react'
import { cn } from '@/shared/lib/cn'

const MENU_ITEM_SELECTOR = '[role="menuitem"]:not([disabled])'

type DropdownMenuProps = {
  trigger: (args: { isOpen: boolean }) => ReactNode
  children: (args: { close: () => void }) => ReactNode
  align?: 'start' | 'end'
  triggerClassName?: string
  panelClassName?: string
}

/**
 * Generic trigger+panel dropdown, following Dialog.tsx's Escape/focus
 * conventions (CLAUDE.md section 30) but without a full focus trap — a
 * menu closes on Escape/outside-click/item-selection rather than keeping
 * focus inside indefinitely.
 */
export function DropdownMenu({ trigger, children, align = 'end', triggerClassName, panelClassName }: DropdownMenuProps) {
  const [isOpen, setIsOpen] = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)
  const triggerRef = useRef<HTMLButtonElement>(null)
  const panelRef = useRef<HTMLDivElement>(null)

  const close = () => setIsOpen(false)

  useEffect(() => {
    if (!isOpen) return

    const firstItem = panelRef.current?.querySelector<HTMLElement>(MENU_ITEM_SELECTOR)
    firstItem?.focus()

    function onPointerDown(event: MouseEvent) {
      if (!containerRef.current?.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        event.preventDefault()
        setIsOpen(false)
        triggerRef.current?.focus()
        return
      }

      if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return
      const items = Array.from(panelRef.current?.querySelectorAll<HTMLElement>(MENU_ITEM_SELECTOR) ?? [])
      if (items.length === 0) return

      event.preventDefault()
      const currentIndex = items.indexOf(document.activeElement as HTMLElement)
      const nextIndex = event.key === 'ArrowDown'
        ? (currentIndex + 1) % items.length
        : (currentIndex - 1 + items.length) % items.length
      items[nextIndex]?.focus()
    }

    document.addEventListener('mousedown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('mousedown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [isOpen])

  return (
    <div ref={containerRef} className="relative inline-block">
      <button
        ref={triggerRef}
        aria-expanded={isOpen}
        aria-haspopup="menu"
        className={cn('outline-none focus-visible:ring-2 focus-visible:ring-brand-600', triggerClassName)}
        type="button"
        onClick={() => setIsOpen((value) => !value)}
      >
        {trigger({ isOpen })}
      </button>

      {isOpen ? (
        <div
          ref={panelRef}
          className={cn(
            'absolute z-50 mt-2 w-[min(16rem,calc(100vw-1.5rem))] rounded-xl border border-border bg-surface p-2 shadow-panel',
            align === 'end' ? 'right-0' : 'left-0',
            panelClassName,
          )}
          role="menu"
        >
          {children({ close })}
        </div>
      ) : null}
    </div>
  )
}

type DropdownMenuItemProps = {
  onClick: () => void
  icon?: ReactNode
  children: ReactNode
  className?: string
}

export function DropdownMenuItem({ onClick, icon, children, className }: DropdownMenuItemProps) {
  return (
    <button
      className={cn(
        'flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-ink outline-none transition-colors hover:bg-subtle focus-visible:ring-2 focus-visible:ring-brand-600',
        className,
      )}
      role="menuitem"
      type="button"
      onClick={onClick}
    >
      {icon}
      {children}
    </button>
  )
}
