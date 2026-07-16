const roleClasses: Record<string, string> = {
  owner: 'bg-violet-50 text-violet-700',
  manager: 'bg-brand-50 text-brand-700',
  staff: 'bg-slate-100 text-slate-700',
}

export function RoleBadge({ code, name }: { code: string; name: string }) {
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${roleClasses[code] ?? 'bg-slate-100 text-slate-700'}`}>{name}</span>
}
