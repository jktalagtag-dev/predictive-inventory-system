import { Edit3, UserCheck, UserX } from 'lucide-react'
import type { ManagedUser } from '@/features/users/types/user'
import { RoleBadge } from '@/features/users/components/RoleBadge'
import { Button } from '@/shared/components/Button'

type UserTableProps = {
  users: ManagedUser[]
  onEdit: (user: ManagedUser) => void
  onStatusChange: (user: ManagedUser) => void
}

export function UserTable({ users, onEdit, onStatusChange }: UserTableProps) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[850px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr>
              <th className="px-5 py-3">User</th>
              <th className="px-5 py-3">Roles</th>
              <th className="px-5 py-3">Branches</th>
              <th className="px-5 py-3">Last sign in</th>
              <th className="px-5 py-3">Status</th>
              <th className="px-5 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {users.map((user) => (
              <tr key={user.id} className="hover:bg-subtle/70">
                <td className="px-5 py-4"><p className="font-semibold text-ink">{user.displayName}</p><p className="mt-1 text-xs text-muted">{user.email}</p></td>
                <td className="px-5 py-4"><div className="flex flex-wrap gap-1.5">{user.roles.map((role) => <RoleBadge key={role.id} code={role.code} name={role.name} />)}</div></td>
                <td className="px-5 py-4 text-muted">{user.branches.map((branch) => branch.name).join(', ') || '—'}</td>
                <td className="px-5 py-4 text-muted">{user.lastLoginAt ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Manila' }).format(new Date(user.lastLoginAt)) : 'Never'}</td>
                <td className="px-5 py-4"><span className={user.isActive ? 'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700' : 'inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700'}>{user.isActive ? 'Active' : 'Inactive'}</span></td>
                <td className="px-5 py-4"><div className="flex justify-end gap-1"><Button aria-label={`Edit ${user.displayName}`} size="icon" variant="ghost" onClick={() => onEdit(user)}><Edit3 aria-hidden="true" size={16} /></Button><Button aria-label={`${user.isActive ? 'Deactivate' : 'Activate'} ${user.displayName}`} size="icon" variant="ghost" onClick={() => onStatusChange(user)}>{user.isActive ? <UserX aria-hidden="true" size={16} /> : <UserCheck aria-hidden="true" size={16} />}</Button></div></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
