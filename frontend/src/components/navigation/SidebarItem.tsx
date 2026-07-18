import type { LucideIcon } from 'lucide-react'
import { NavLink } from 'react-router-dom'

import type { AppRoutePath } from '@/components/navigation/types'
import { cn } from '@/shared/lib/cn'

interface SidebarItemProps {
  label: string
  to: AppRoutePath
  isExpanded: boolean
  icon?: LucideIcon
  variant?: 'primary' | 'nested'
  ariaLabel?: string
  onNavigate?: () => void
}

export function SidebarItem({
  label,
  to,
  isExpanded,
  icon: Icon,
  variant = 'nested',
  ariaLabel,
  onNavigate,
}: SidebarItemProps) {
  const isPrimary = variant === 'primary'

  return (
    <NavLink
      aria-label={ariaLabel ?? label}
      to={to}
      onClick={onNavigate}
      className={({ isActive }) =>
        cn(
          'relative flex items-center rounded-lg outline-none transition-colors duration-200 ease-out focus-visible:ring-2 focus-visible:ring-info',
          'before:absolute before:left-0 before:rounded-r-full before:bg-transparent before:transition-colors before:duration-150',
          isPrimary ? 'h-11 gap-3 px-3 text-sm font-semibold before:top-2 before:h-7 before:w-[3px]' : 'h-11 px-3 pl-4 text-sm before:top-3 before:h-5 before:w-[3px]',
          isActive ? 'bg-white/10 text-white before:bg-info' : 'text-white/60 hover:bg-white/5 hover:text-white',
        )
      }
    >
      {({ isActive }) => (
        <>
          {Icon ? (
            <Icon
              aria-hidden="true"
              className={cn('shrink-0 transition-colors duration-150', isActive && 'text-info')}
              size={19}
            />
          ) : null}
          {isExpanded ? <span className="truncate">{label}</span> : <span className="lg:sr-only">{label}</span>}
        </>
      )}
    </NavLink>
  )
}
