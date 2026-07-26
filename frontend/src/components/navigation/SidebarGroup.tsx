import { ChevronDown } from 'lucide-react'

import type { NavigationGroupKey, VisibleSidebarGroup } from '@/components/navigation/types'
import { SidebarItem } from '@/components/navigation/SidebarItem'
import { cn } from '@/shared/lib/cn'

interface SidebarGroupProps {
  group: VisibleSidebarGroup
  isExpanded: boolean
  isSidebarExpanded: boolean
  onToggle: (groupKey: NavigationGroupKey) => void
  onNavigate?: () => void
}

export function SidebarGroup({ group, isExpanded, isSidebarExpanded, onToggle, onNavigate }: SidebarGroupProps) {
  const GroupIcon = group.icon
  const groupPanelId = `sidebar-group-${group.key}`

  return (
    <div className="space-y-1">
      <button
        aria-controls={groupPanelId}
        aria-expanded={isExpanded}
        aria-label={group.ariaLabel ?? `Toggle ${group.label}`}
        className={cn(
          'flex h-11 w-full items-center rounded-lg px-3 text-left outline-none transition-colors duration-200 ease-out focus-visible:ring-2 focus-visible:ring-info',
          isExpanded ? 'bg-white/[0.07] text-white' : 'text-white/60 hover:bg-white/5 hover:text-white',
        )}
        type="button"
        onClick={() => onToggle(group.key)}
      >
        <GroupIcon aria-hidden="true" className="shrink-0" size={18} />

        {isSidebarExpanded ? (
          <>
            <span className="ml-3 min-w-0 flex-1 truncate text-sm font-semibold">{group.label}</span>
            <ChevronDown
              aria-hidden="true"
              className={cn('ml-2 shrink-0 transition-transform duration-300 ease-out', isExpanded && 'rotate-180')}
              size={16}
            />
          </>
        ) : null}
      </button>

      {isSidebarExpanded ? (
        <div
          id={groupPanelId}
          className={cn(
            'overflow-hidden transition-[max-height,opacity] duration-300 ease-out motion-reduce:transition-none',
            isExpanded ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0',
          )}
        >
          <div className="ml-[1.375rem] space-y-1 border-l border-white/10 pl-2 pt-1">
            {group.items.map((item) => (
              <SidebarItem
                key={item.key}
                ariaLabel={item.ariaLabel}
                isExpanded={isSidebarExpanded}
                label={item.label}
                to={item.to}
                onNavigate={onNavigate}
              />
            ))}
          </div>
        </div>
      ) : null}
    </div>
  )
}
