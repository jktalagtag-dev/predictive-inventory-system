import { Lock, Pencil, ShieldAlert } from 'lucide-react'
import type { Setting } from '@/features/settings/types/settings'
import { Button } from '@/shared/components/Button'
import { RecordCard } from '@/shared/components/RecordCard'
import { Table, TableBody, TableCell, TableEmptyState, TableHead, TableHeaderCell, TableRow } from '@/shared/components/Table'

function formatValue(setting: Setting): string {
  if (setting.isRedacted) return 'Hidden'
  if (setting.value === null || setting.value === undefined) return '—'
  if (typeof setting.value === 'boolean') return setting.value ? 'Yes' : 'No'
  if (typeof setting.value === 'object') return JSON.stringify(setting.value)
  return String(setting.value)
}

const settingTitle = (setting: Setting) => (
  <span className="flex items-center gap-1.5 font-mono text-xs font-semibold text-ink">
    {setting.key}
    {setting.ownerOnly ? <Lock aria-label="Owner only" className="text-muted" size={13} /> : null}
    {setting.isSensitive ? <ShieldAlert aria-label="Sensitive value" className="text-warning-text" size={13} /> : null}
  </span>
)

export function SettingsTable({ settings, canManage, onEdit }: { settings: Setting[]; canManage: boolean; onEdit: (setting: Setting) => void }) {
  return (
    <>
      <div className="space-y-3 md:hidden">
        {settings.length === 0 ? (
          <p className="rounded-card border border-border bg-surface p-6 text-center text-sm text-muted shadow-panel">No settings found.</p>
        ) : (
          settings.map((setting) => (
            <RecordCard
              key={setting.key}
              title={settingTitle(setting)}
              subtitle={setting.description}
              fields={[
                { label: 'Value', value: <span className="tabular-nums">{formatValue(setting)}</span> },
                { label: 'Scope', value: `${setting.branchId ? `Branch ${setting.branchId}` : 'Global'}${setting.isOverridden ? '' : ' (default)'}` },
              ]}
              actions={canManage ? (
                <Button aria-label={`Edit ${setting.key}`} disabled={setting.isRedacted} size="icon" variant="ghost" onClick={() => onEdit(setting)}>
                  <Pencil aria-hidden="true" size={16} />
                </Button>
              ) : undefined}
            />
          ))
        )}
      </div>

      <div className="hidden md:block">
        <Table minWidth={700}>
          <TableHead>
            <tr>
              <TableHeaderCell>Setting</TableHeaderCell>
              <TableHeaderCell>Value</TableHeaderCell>
              <TableHeaderCell>Scope</TableHeaderCell>
              <TableHeaderCell align="right">Actions</TableHeaderCell>
            </tr>
          </TableHead>
          <TableBody>
            {settings.length === 0 ? (
              <TableEmptyState colSpan={4}>No settings found.</TableEmptyState>
            ) : (
              settings.map((setting) => (
                <TableRow key={setting.key}>
                  <TableCell>
                    {settingTitle(setting)}
                    <p className="mt-1 text-xs text-muted">{setting.description}</p>
                  </TableCell>
                  <TableCell className="tabular-nums">{formatValue(setting)}</TableCell>
                  <TableCell>{setting.branchId ? `Branch ${setting.branchId}` : 'Global'}{setting.isOverridden ? '' : ' (default)'}</TableCell>
                  <TableCell align="right">
                    <div className="flex justify-end">
                      {canManage ? (
                        <Button aria-label={`Edit ${setting.key}`} disabled={setting.isRedacted} size="icon" variant="ghost" onClick={() => onEdit(setting)}>
                          <Pencil aria-hidden="true" size={16} />
                        </Button>
                      ) : null}
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
