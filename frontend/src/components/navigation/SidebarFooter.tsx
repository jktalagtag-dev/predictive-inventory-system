import { PanelLeftClose, PanelLeftOpen } from 'lucide-react'

interface SidebarFooterProps {
  isSidebarOpen: boolean
  isSidebarExpanded: boolean
  onToggleSidebar: () => void
}

export function SidebarFooter({ isSidebarOpen, isSidebarExpanded, onToggleSidebar }: SidebarFooterProps) {
  return (
    <div className="hidden border-t border-white/10 p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] lg:block">
      <button
        aria-label={isSidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'}
        className="flex h-11 w-full items-center gap-3 rounded-lg px-3 text-sm font-medium text-white/60 outline-none transition-colors duration-200 ease-out hover:bg-white/5 hover:text-white focus-visible:ring-2 focus-visible:ring-info"
        type="button"
        onClick={onToggleSidebar}
      >
        {isSidebarOpen ? (
          <PanelLeftClose aria-hidden="true" className="shrink-0" size={18} />
        ) : (
          <PanelLeftOpen aria-hidden="true" className="shrink-0" size={18} />
        )}

        {isSidebarExpanded ? <span>{isSidebarOpen ? 'Collapse' : 'Expand'}</span> : null}
      </button>
    </div>
  )
}
