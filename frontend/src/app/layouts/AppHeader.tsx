import { Menu } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthProvider'
import { UserMenu } from '@/features/auth/components/UserMenu'
import { SyncStatusIndicator } from '@/features/sync/components/SyncStatusIndicator'
import { Button } from '@/shared/components/Button'
import { useUiStore } from '@/shared/state/uiStore'

export function AppHeader() {
  const toggleMobileNav = useUiStore((state) => state.toggleMobileNav)
  const { session } = useAuth()

  return (
    <header className="fixed inset-x-0 top-0 z-30 flex h-16 items-center border-b border-border bg-surface/95 px-4 backdrop-blur sm:px-6">
      <div className="flex min-w-0 items-center gap-3">
        <Button
          aria-label="Toggle navigation"
          className="lg:hidden"
          variant="ghost"
          size="icon"
          onClick={toggleMobileNav}
        >
          <Menu aria-hidden="true" size={20} />
        </Button>
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold text-ink">Predictive Inventory System</p>
          <p className="truncate text-xs text-muted">Steven Hydrotech Exponent</p>
        </div>
      </div>
      <div className="ml-auto flex items-center gap-2">
        <SyncStatusIndicator userId={session?.user.id} />
        <UserMenu />
      </div>
    </header>
  )
}
