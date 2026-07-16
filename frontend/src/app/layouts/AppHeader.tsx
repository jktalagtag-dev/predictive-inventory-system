import { LogOut, Menu, Moon, Sun } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthProvider'
import { Button } from '@/shared/components/Button'
import { useUiStore } from '@/shared/state/uiStore'

export function AppHeader() {
  const theme = useUiStore((state) => state.theme)
  const toggleSidebar = useUiStore((state) => state.toggleSidebar)
  const toggleTheme = useUiStore((state) => state.toggleTheme)
  const { logout, session } = useAuth()

  const handleLogout = async () => {
    await logout()
    // A hard navigation (not react-router's navigate) guarantees every
    // in-memory store — React Query cache, Zustand state, component
    // closures — is discarded on sign-out, not just the auth session
    // query. Client-side navigation left this racing against in-flight
    // requests started before logout, which could re-populate the
    // session cache with stale authenticated data after clear().
    window.location.assign('/login')
  }

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
        <span className="hidden rounded-full bg-subtle px-3 py-1 text-xs font-medium text-muted md:inline-flex">
          {session?.user.displayName}
        </span>
        <Button
          aria-label={theme === 'light' ? 'Switch to dark theme' : 'Switch to light theme'}
          variant="ghost"
          size="icon"
          onClick={toggleTheme}
        >
          {theme === 'light' ? <Moon aria-hidden="true" size={18} /> : <Sun aria-hidden="true" size={18} />}
        </Button>
        <Button aria-label="Sign out" variant="ghost" size="icon" onClick={() => void handleLogout()}>
          <LogOut aria-hidden="true" size={18} />
        </Button>
      </div>
    </header>
  )
}
