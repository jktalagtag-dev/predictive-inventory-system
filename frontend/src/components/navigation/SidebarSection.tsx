import type { NavigationGroupKey, VisibleSidebarSection } from '@/components/navigation/types'
import { SidebarGroup } from '@/components/navigation/SidebarGroup'

interface SidebarSectionProps {
  section: VisibleSidebarSection
  expandedGroupKey: NavigationGroupKey | null
  isSidebarExpanded: boolean
  onGroupToggle: (groupKey: NavigationGroupKey) => void
  onNavigate?: () => void
}

export function SidebarSection({
  section,
  expandedGroupKey,
  isSidebarExpanded,
  onGroupToggle,
  onNavigate,
}: SidebarSectionProps) {
  return (
    <section aria-labelledby={`sidebar-section-${section.key}`} className="space-y-2">
      {isSidebarExpanded ? (
        <h2
          id={`sidebar-section-${section.key}`}
          className="px-3 text-[0.68rem] font-semibold uppercase tracking-normal text-white/35"
        >
          {section.label}
        </h2>
      ) : (
        <span id={`sidebar-section-${section.key}`} className="sr-only">
          {section.label}
        </span>
      )}

      <div className="space-y-1">
        {section.groups.map((group) => (
          <SidebarGroup
            key={group.key}
            group={group}
            isExpanded={expandedGroupKey === group.key}
            isSidebarExpanded={isSidebarExpanded}
            onNavigate={onNavigate}
            onToggle={onGroupToggle}
          />
        ))}
      </div>
    </section>
  )
}
