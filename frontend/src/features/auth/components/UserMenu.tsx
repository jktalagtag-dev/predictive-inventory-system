import { LogOut, Moon, Sun } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthProvider'
import { formatRole } from '@/features/auth/lib/formatRole'
import { Avatar } from '@/shared/components/Avatar'
import { DropdownMenu, DropdownMenuItem } from '@/shared/components/DropdownMenu'
import { useUiStore } from '@/shared/state/uiStore'

export function UserMenu() {
  const { session, logout } = useAuth()
  const theme = useUiStore((state) => state.theme)
  const toggleTheme = useUiStore((state) => state.toggleTheme)

  if (!session) return null

  const { displayName, email, avatarUrl, roles } = session.user
  const position = formatRole(roles[0])

  const handleLogout = async () => {
    await logout()
    // A hard navigation guarantees every in-memory store — React Query
    // cache, Zustand state, component closures — is discarded on
    // sign-out, not just the auth session query.
    window.location.assign('/login')
  }

  return (
    <DropdownMenu
      align="end"
      triggerClassName="flex items-center gap-2 rounded-full py-1 pl-1 pr-3 hover:bg-subtle"
      trigger={() => (
        <>
          <Avatar name={displayName} src={avatarUrl} size="sm" />
          <span className="hidden text-left sm:block">
            <span className="block text-sm font-semibold leading-tight text-ink">{displayName}</span>
            <span className="block text-xs leading-tight text-muted">{position}</span>
          </span>
        </>
      )}
    >
      {({ close }) => (
        <>
          <div className="flex items-center gap-3 border-b border-border px-2 pb-3">
            <Avatar name={displayName} src={avatarUrl} size="md" />
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold text-ink">{displayName}</p>
              <p className="truncate text-xs text-muted">{email}</p>
              <p className="truncate text-xs text-muted">{position}</p>
            </div>
          </div>
          <div className="mt-2 space-y-1">
            <DropdownMenuItem
              icon={theme === 'light' ? <Moon aria-hidden="true" size={18} /> : <Sun aria-hidden="true" size={18} />}
              onClick={() => {
                toggleTheme()
                close()
              }}
            >
              {theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'}
            </DropdownMenuItem>
            <DropdownMenuItem
              icon={<LogOut aria-hidden="true" size={18} />}
              onClick={() => {
                close()
                void handleLogout()
              }}
            >
              Sign out
            </DropdownMenuItem>
          </div>
        </>
      )}
    </DropdownMenu>
  )
}
