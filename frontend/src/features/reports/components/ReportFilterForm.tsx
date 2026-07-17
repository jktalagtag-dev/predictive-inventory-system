import type { FormEvent } from 'react'
import type { ReportDefinition, ReportFilterValues } from '@/features/reports/types/report'
import { Button } from '@/shared/components/Button'

type BranchOption = { id: string; name: string }

type ReportFilterFormProps = {
  definition: ReportDefinition
  values: ReportFilterValues
  branchOptions: BranchOption[]
  isRunning: boolean
  onChange: (key: string, value: string | boolean | undefined) => void
  onRun: () => void
}

const inputClass = 'mt-1 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20'

export function ReportFilterForm({ definition, values, branchOptions, isRunning, onChange, onRun }: ReportFilterFormProps) {
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onRun()
  }

  return (
    <form className="grid gap-3 rounded-xl border border-border bg-surface p-4 shadow-panel sm:grid-cols-3" onSubmit={submit}>
      {definition.filters.map((filter) => (
        <label key={filter.key} className="text-xs font-semibold text-muted">
          {filter.key}{filter.required ? ' *' : ''}
          {filter.key === 'branchId' ? (
            <select className={inputClass} required={filter.required} value={(values.branchId as string) ?? ''} onChange={(event) => onChange('branchId', event.target.value || undefined)}>
              <option value="">Select a branch</option>
              {branchOptions.map((branch) => <option key={branch.id} value={branch.id}>{branch.name}</option>)}
            </select>
          ) : filter.type === 'date' ? (
            <input className={inputClass} required={filter.required} type="date" value={(values[filter.key] as string) ?? ''} onChange={(event) => onChange(filter.key, event.target.value || undefined)} />
          ) : filter.type === 'boolean' ? (
            <select className={inputClass} value={values[filter.key] === undefined ? '' : String(values[filter.key])} onChange={(event) => onChange(filter.key, event.target.value === '' ? undefined : event.target.value === 'true')}>
              <option value="">Any</option>
              <option value="true">Yes</option>
              <option value="false">No</option>
            </select>
          ) : (
            <input className={inputClass} required={filter.required} type={filter.type === 'integer' ? 'number' : 'text'} value={(values[filter.key] as string) ?? ''} onChange={(event) => onChange(filter.key, event.target.value || undefined)} />
          )}
        </label>
      ))}
      <div className="flex items-end"><Button disabled={isRunning} type="submit">{isRunning ? 'Running…' : 'Run report'}</Button></div>
    </form>
  )
}
