import { type FormEvent, useEffect, useState } from 'react'
import { X } from 'lucide-react'
import type { ManagedUser, UserFormValues, UserRole } from '@/features/users/types/user'
import { Button } from '@/shared/components/Button'

type UserFormDialogProps = {
  user?: ManagedUser
  isSaving: boolean
  onClose: () => void
  onSave: (values: UserFormValues) => void
}

const roles: UserRole[] = ['Owner', 'Manager', 'Staff']

function formValuesFrom(user?: ManagedUser): UserFormValues {
  return user
    ? { firstName: user.firstName, lastName: user.lastName, email: user.email, roles: user.roles, status: user.status }
    : { firstName: '', lastName: '', email: '', roles: ['Staff'], status: 'active' }
}

export function UserFormDialog({ user, isSaving, onClose, onSave }: UserFormDialogProps) {
  const [values, setValues] = useState<UserFormValues>(() => formValuesFrom(user))

  useEffect(() => setValues(formValuesFrom(user)), [user])

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onSave(values)
  }

  const toggleRole = (role: UserRole) => {
    setValues((state) => ({
      ...state,
      roles: state.roles.includes(role) ? state.roles.filter((item) => item !== role) : [...state.roles, role],
    }))
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation">
      <section aria-labelledby="user-form-title" aria-modal="true" className="w-full max-w-lg rounded-xl border border-border bg-surface p-6 shadow-panel" role="dialog">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h2 id="user-form-title" className="text-lg font-bold text-ink">{user ? 'Edit user' : 'Create user'}</h2>
            <p className="mt-1 text-sm text-muted">Assign only the access required for the user’s responsibilities.</p>
          </div>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>
        <form className="mt-6 space-y-4" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <label className="text-sm font-semibold text-ink">First name
              <input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.firstName} onChange={(event) => setValues((state) => ({ ...state, firstName: event.target.value }))} />
            </label>
            <label className="text-sm font-semibold text-ink">Last name
              <input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.lastName} onChange={(event) => setValues((state) => ({ ...state, lastName: event.target.value }))} />
            </label>
          </div>
          <label className="block text-sm font-semibold text-ink">Email address
            <input className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required type="email" value={values.email} onChange={(event) => setValues((state) => ({ ...state, email: event.target.value }))} />
          </label>
          <fieldset>
            <legend className="text-sm font-semibold text-ink">Roles</legend>
            <div className="mt-2 flex flex-wrap gap-2">
              {roles.map((role) => (
                <label key={role} className="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-2 text-sm text-ink">
                  <input checked={values.roles.includes(role)} type="checkbox" onChange={() => toggleRole(role)} />
                  {role}
                </label>
              ))}
            </div>
          </fieldset>
          <label className="block text-sm font-semibold text-ink">Status
            <select className="mt-2 h-10 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.status} onChange={(event) => setValues((state) => ({ ...state, status: event.target.value as UserFormValues['status'] }))}>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </label>
          <div className="flex justify-end gap-3 border-t border-border pt-5">
            <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
            <Button disabled={isSaving || values.roles.length === 0} type="submit">{isSaving ? 'Saving' : user ? 'Save changes' : 'Create user'}</Button>
          </div>
        </form>
      </section>
    </div>
  )
}
