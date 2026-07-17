import { Lock, Pencil, ShieldAlert } from 'lucide-react'
import type { Setting } from '@/features/settings/types/settings'
import { Button } from '@/shared/components/Button'

function formatValue(setting: Setting): string {
  if (setting.isRedacted) return 'Hidden'
  if (setting.value === null || setting.value === undefined) return '—'
  if (typeof setting.value === 'boolean') return setting.value ? 'Yes' : 'No'
  if (typeof setting.value === 'object') return JSON.stringify(setting.value)
  return String(setting.value)
}

export function SettingsTable({ settings, canManage, onEdit }: { settings: Setting[]; canManage: boolean; onEdit: (setting: Setting) => void }) {
  return (
    <section className="overflow-hidden rounded-xl border border-border bg-surface shadow-panel">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[700px] text-sm">
          <thead className="bg-subtle text-left text-xs font-semibold text-muted">
            <tr>
              <th className="px-4 py-3">Setting</th>
              <th className="px-4 py-3">Value</th>
              <th className="px-4 py-3">Scope</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {settings.map((setting) => (
              <tr key={setting.key} className="hover:bg-subtle/70">
                <td className="px-4 py-4">
                  <p className="flex items-center gap-1.5 font-mono text-xs font-semibold text-ink">
                    {setting.key}
                    {setting.ownerOnly ? <Lock aria-label="Owner only" className="text-muted" size={13} /> : null}
                    {setting.isSensitive ? <ShieldAlert aria-label="Sensitive value" className="text-amber-600" size={13} /> : null}
                  </p>
                  <p className="mt-1 text-xs text-muted">{setting.description}</p>
                </td>
                <td className="px-4 py-4 tabular-nums">{formatValue(setting)}</td>
                <td className="px-4 py-4">{setting.branchId ? `Branch ${setting.branchId}` : 'Global'}{setting.isOverridden ? '' : ' (default)'}</td>
                <td className="px-4 py-4">
                  <div className="flex justify-end">
                    {canManage ? (
                      <Button aria-label={`Edit ${setting.key}`} disabled={setting.isRedacted} size="icon" variant="ghost" onClick={() => onEdit(setting)}>
                        <Pencil aria-hidden="true" size={16} />
                      </Button>
                    ) : null}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}
