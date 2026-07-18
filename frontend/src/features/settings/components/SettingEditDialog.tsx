import { type FormEvent, useState } from 'react'
import { X } from 'lucide-react'
import type { Setting } from '@/features/settings/types/settings'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'

type SettingEditDialogProps = {
  setting: Setting
  isSaving: boolean
  error: ApiError | null
  onClose: () => void
  onSave: (value: string | number | boolean) => void
}

export function SettingEditDialog({ setting, isSaving, error, onClose, onSave }: SettingEditDialogProps) {
  const [value, setValue] = useState<string>(setting.isRedacted ? '' : String(setting.value ?? ''))
  const [boolValue, setBoolValue] = useState<boolean>(setting.value === true)

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (setting.valueType === 'boolean') {
      onSave(boolValue)
    } else if (setting.valueType === 'integer') {
      onSave(parseInt(value, 10))
    } else {
      onSave(value)
    }
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation">
      <section aria-labelledby="setting-edit-title" aria-modal="true" className="max-h-[85dvh] w-full max-w-md overflow-y-auto rounded-card border border-border bg-surface p-6 shadow-panel sm:p-8" role="dialog">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h2 id="setting-edit-title" className="font-mono text-base font-bold text-ink">{setting.key}</h2>
            <p className="mt-1 text-sm text-muted">{setting.description}</p>
          </div>
          <Button aria-label="Close" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>

        {error ? (
          <div className="mt-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger-text" role="alert">
            {error.code === 'VERSION_CONFLICT' ? 'This setting was changed by someone else. Close and reopen to see the latest value.' : error.message}
          </div>
        ) : null}

        <form className="mt-6 grid gap-4" onSubmit={submit}>
          {setting.valueType === 'boolean' ? (
            <label className="flex items-center gap-2 text-sm font-semibold text-ink">
              <input checked={boolValue} type="checkbox" onChange={(event) => setBoolValue(event.target.checked)} /> Enabled
            </label>
          ) : (
            <label className="text-sm font-semibold text-ink">Value
              <input
                className="mt-2 h-10 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
                required
                step={setting.valueType === 'decimal' ? '0.0001' : setting.valueType === 'integer' ? '1' : undefined}
                type={setting.valueType === 'integer' || setting.valueType === 'decimal' ? 'number' : setting.valueType === 'date' ? 'date' : 'text'}
                value={value}
                onChange={(event) => setValue(event.target.value)}
              />
            </label>
          )}
          <div className="flex justify-end gap-3 border-t border-border pt-5">
            <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
            <Button disabled={isSaving} type="submit">{isSaving ? 'Saving…' : 'Save'}</Button>
          </div>
        </form>
      </section>
    </div>
  )
}
