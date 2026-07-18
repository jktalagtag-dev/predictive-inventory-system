import { Edit3, UserCheck, UserX } from 'lucide-react'
import type { ManagedUser } from '@/features/users/types/user'
import { RoleBadge } from '@/features/users/components/RoleBadge'
import { Avatar } from '@/shared/components/Avatar'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

type UserTableProps = {
  users: ManagedUser[]
  onEdit: (user: ManagedUser) => void
  onStatusChange: (user: ManagedUser) => void
}

const formatLastLogin = (value: string | null) =>
  value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Manila' }).format(new Date(value)) : 'Never'

const statusBadge = (isActive: boolean) => (
  <span className={isActive ? 'inline-flex rounded-full bg-success/10 px-2.5 py-1 text-xs font-semibold text-success-text' : 'inline-flex rounded-full bg-subtle px-2.5 py-1 text-xs font-semibold text-muted'}>
    {isActive ? 'Active' : 'Inactive'}
  </span>
)

export function UserTable({ users, onEdit, onStatusChange }: UserTableProps) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {users.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No users match these filters.</p>
        ) : (
          users.map((user) => (
            <RecordCard
              key={user.id}
              badge={statusBadge(user.isActive)}
              title={
                <span className="flex items-center gap-3">
                  <Avatar name={user.displayName} src={user.avatarUrl} size="sm" />
                  <span className="truncate">{user.displayName}</span>
                </span>
              }
              subtitle={user.email}
              fields={[
                { label: 'Roles', value: <span className="flex flex-wrap gap-1.5">{user.roles.map((role) => <RoleBadge key={role.id} code={role.code} name={role.name} />)}</span>, full: true },
                { label: 'Branches', value: user.branches.map((branch) => branch.name).join(', ') || '—', full: true },
                { label: 'Last sign in', value: formatLastLogin(user.lastLoginAt), full: true },
              ]}
              actions={
                <>
                  <Button aria-label={`Edit ${user.displayName}`} size="icon" variant="ghost" onClick={() => onEdit(user)}><Edit3 aria-hidden="true" size={16} /></Button>
                  <Button aria-label={`${user.isActive ? 'Deactivate' : 'Activate'} ${user.displayName}`} size="icon" variant="ghost" onClick={() => onStatusChange(user)}>
                    {user.isActive ? <UserX aria-hidden="true" size={16} /> : <UserCheck aria-hidden="true" size={16} />}
                  </Button>
                </>
              }
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={850}>
          <TableHead>
            <tr>
              <TableHeaderCell>User</TableHeaderCell>
              <TableHeaderCell>Roles</TableHeaderCell>
              <TableHeaderCell>Branches</TableHeaderCell>
              <TableHeaderCell>Last sign in</TableHeaderCell>
              <TableHeaderCell>Status</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {users.length === 0 ? (
              <TableEmptyState colSpan={6}>No users match these filters.</TableEmptyState>
            ) : (
              users.map((user) => (
                <TableRow key={user.id}>
                  <TableCell>
                    <div className="flex items-center gap-3">
                      <Avatar name={user.displayName} src={user.avatarUrl} size="sm" />
                      <div><p className="font-semibold text-ink">{user.displayName}</p><p className="mt-1 text-xs text-muted">{user.email}</p></div>
                    </div>
                  </TableCell>
                  <TableCell><div className="flex flex-wrap gap-1.5">{user.roles.map((role) => <RoleBadge key={role.id} code={role.code} name={role.name} />)}</div></TableCell>
                  <TableCell className="text-muted">{user.branches.map((branch) => branch.name).join(', ') || '—'}</TableCell>
                  <TableCell className="text-muted">{formatLastLogin(user.lastLoginAt)}</TableCell>
                  <TableCell>{statusBadge(user.isActive)}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end gap-1">
                      <Button aria-label={`Edit ${user.displayName}`} size="icon" variant="ghost" onClick={() => onEdit(user)}><Edit3 aria-hidden="true" size={16} /></Button>
                      <Button aria-label={`${user.isActive ? 'Deactivate' : 'Activate'} ${user.displayName}`} size="icon" variant="ghost" onClick={() => onStatusChange(user)}>
                        {user.isActive ? <UserX aria-hidden="true" size={16} /> : <UserCheck aria-hidden="true" size={16} />}
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </>
  )
}
