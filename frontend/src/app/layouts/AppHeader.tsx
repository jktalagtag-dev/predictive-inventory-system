import { Menu, Moon, Sun } from 'lucide-react'
import { Button } from '@/shared/components/Button'
import { useUiStore } from '@/shared/state/uiStore'

export function AppHeader() {
  const theme = useUiStore((state) => state.theme)
  const toggleSidebar = useUiStore((state) => state.toggleSidebar)
  const toggleTheme = useUiStore((state) => state.toggleTheme)

  return (
    <header className="fixed inset-x-0 top-0 z-30 flex h-16 items-center border-b border-border bg-surface/95 px-4 backdrop-blur sm:px-6">
      <div className="flex min-w-0 items-center gap-3">
        <Button
          aria-label="Toggle navigation"
          variant="ghost"
          size="icon"
          onClick={toggleSidebar}
        >
          <Menu aria-hidden="true" size={20} />
        </Button>
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold text-ink">Predictive Inventory System</p>
          <p className="truncate text-xs text-muted">Steven Hydrotech Exponent</p>
        </div>
      </div>
      <div className="ml-auto flex items-center gap-2">
        <span className="hidden rounded-full bg-subtle px-3 py-1 text-xs font-medium text-muted sm:inline-flex">
          Foundation mode
        </span>
        <Button
          aria-label={theme === 'light' ? 'Switch to dark theme' : 'Switch to light theme'}
          variant="ghost"
          size="icon"
          onClick={toggleTheme}
        >
          {theme === 'light' ? <Moon aria-hidden="true" size={18} /> : <Sun aria-hidden="true" size={18} />}
        </Button>
      </div>
    </header>
  )
}
