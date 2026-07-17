import { Badge } from '@/shared/components/Badge'

// Role identity, not a workflow status, so it intentionally keeps its
// own color mapping outside the five status tones (CLAUDE.md section 28
// reserves those for stable state meanings) while still using Badge for
// consistent shape, padding, and typography.
const roleClasses: Record<string, string> = {
  owner: 'bg-violet-50 text-violet-700',
  manager: 'bg-brand-50 text-brand-700',
  staff: 'bg-slate-100 text-slate-700',
}

export function RoleBadge({ code, name }: { code: string; name: string }) {
  return <Badge className={roleClasses[code] ?? 'bg-slate-100 text-slate-700'}>{name}</Badge>
}
