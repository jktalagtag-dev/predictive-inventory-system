import type { UserRole } from '@/features/users/types/user'

const roleClasses: Record<UserRole, string> = {
  Owner: 'bg-violet-50 text-violet-700',
  Manager: 'bg-brand-50 text-brand-700',
  Staff: 'bg-slate-100 text-slate-700',
}

export function RoleBadge({ role }: { role: UserRole }) {
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${roleClasses[role]}`}>{role}</span>
}
