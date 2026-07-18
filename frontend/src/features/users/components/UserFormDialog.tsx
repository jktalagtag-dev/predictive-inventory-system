import { type FormEvent, useEffect, useState } from 'react'
import { X } from 'lucide-react'
import type { BranchOption, ManagedUser, RoleOption, UserFormValues } from '@/features/users/types/user'
import { Button } from '@/shared/components/Button'
import { cn } from '@/shared/lib/cn'
import { modalOverlayClass, modalPanelClass, sheetBodyClass, sheetFooterClass, sheetHeaderClass } from '@/shared/lib/modalClasses'

type UserFormDialogProps = {
  user?: ManagedUser
  roleOptions: RoleOption[]
  branchOptions: BranchOption[]
  isSaving: boolean
  onClose: () => void
  onSave: (values: UserFormValues) => void
}

function formValuesFrom(user?: ManagedUser): UserFormValues {
  if (!user) {
    return { firstName: '', lastName: '', email: '', phone: '', roleIds: [], branchIds: [], defaultBranchId: '', isActive: true }
  }
  const defaultBranch = user.branches.find((branch) => branch.isDefault)
  return {
    firstName: user.firstName,
    lastName: user.lastName,
    email: user.email,
    phone: user.phone ?? '',
    roleIds: user.roles.map((role) => role.id),
    branchIds: user.branches.map((branch) => branch.id),
    defaultBranchId: defaultBranch?.id ?? user.branches[0]?.id ?? '',
    isActive: user.isActive,
  }
}

export function UserFormDialog({ user, roleOptions, branchOptions, isSaving, onClose, onSave }: UserFormDialogProps) {
  const [values, setValues] = useState<UserFormValues>(() => formValuesFrom(user))

  useEffect(() => setValues(formValuesFrom(user)), [user])

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onSave(values)
  }

  const toggleRole = (roleId: string) => {
    setValues((state) => ({
      ...state,
      roleIds: state.roleIds.includes(roleId) ? state.roleIds.filter((id) => id !== roleId) : [...state.roleIds, roleId],
    }))
  }

  const toggleBranch = (branchId: string) => {
    setValues((state) => {
      const isSelected = state.branchIds.includes(branchId)
      const branchIds = isSelected ? state.branchIds.filter((id) => id !== branchId) : [...state.branchIds, branchId]
      const defaultBranchId = isSelected && state.defaultBranchId === branchId ? (branchIds[0] ?? '') : state.defaultBranchId || branchId
      return { ...state, branchIds, defaultBranchId }
    })
  }

  const isValid = values.roleIds.length > 0 && values.branchIds.length > 0 && values.defaultBranchId !== ''

  return (
    <div className={modalOverlayClass} role="presentation">
      <section aria-labelledby="user-form-title" aria-modal="true" className={modalPanelClass('sm:max-w-lg')} role="dialog">
        <div className={sheetHeaderClass}>
          <div>
            <h2 id="user-form-title" className="text-lg font-bold text-ink">{user ? 'Edit user' : 'Create user'}</h2>
            <p className="mt-1 text-sm text-muted">Assign only the access required for the user’s responsibilities.</p>
          </div>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}><X aria-hidden="true" size={18} /></Button>
        </div>
        <form className="flex min-h-0 flex-1 flex-col" onSubmit={submit}>
          <div className={cn(sheetBodyClass, 'space-y-4')}>
            <div className="grid gap-4 sm:grid-cols-2">
              <label className="text-sm font-semibold text-ink">First name
                <input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.firstName} onChange={(event) => setValues((state) => ({ ...state, firstName: event.target.value }))} />
              </label>
              <label className="text-sm font-semibold text-ink">Last name
                <input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required value={values.lastName} onChange={(event) => setValues((state) => ({ ...state, lastName: event.target.value }))} />
              </label>
            </div>
            <label className="block text-sm font-semibold text-ink">Email address
              <input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" required type="email" value={values.email} onChange={(event) => setValues((state) => ({ ...state, email: event.target.value }))} />
            </label>
            <label className="block text-sm font-semibold text-ink">Phone
              <input className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.phone} onChange={(event) => setValues((state) => ({ ...state, phone: event.target.value }))} />
            </label>
            <fieldset>
              <legend className="text-sm font-semibold text-ink">Roles</legend>
              <div className="mt-2 flex flex-wrap gap-2">
                {roleOptions.map((role) => (
                  <label key={role.id} className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border px-3 py-2 text-sm text-ink">
                    <input checked={values.roleIds.includes(role.id)} type="checkbox" onChange={() => toggleRole(role.id)} />
                    {role.name}
                  </label>
                ))}
              </div>
            </fieldset>
            <fieldset>
              <legend className="text-sm font-semibold text-ink">Branches</legend>
              <div className="mt-2 flex flex-wrap gap-2">
                {branchOptions.map((branch) => (
                  <label key={branch.id} className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border px-3 py-2 text-sm text-ink">
                    <input checked={values.branchIds.includes(branch.id)} type="checkbox" onChange={() => toggleBranch(branch.id)} />
                    {branch.name}
                  </label>
                ))}
              </div>
            </fieldset>
            {values.branchIds.length > 0 ? (
              <label className="block text-sm font-semibold text-ink">Default branch
                <select className="mt-2 h-11 w-full rounded-xl border border-border bg-surface px-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20" value={values.defaultBranchId} onChange={(event) => setValues((state) => ({ ...state, defaultBranchId: event.target.value }))}>
                  {branchOptions.filter((branch) => values.branchIds.includes(branch.id)).map((branch) => (
                    <option key={branch.id} value={branch.id}>{branch.name}</option>
                  ))}
                </select>
              </label>
            ) : null}
            <label className="flex items-center gap-2 text-sm text-ink">
              <input checked={values.isActive} type="checkbox" onChange={(event) => setValues((state) => ({ ...state, isActive: event.target.checked }))} />
              Active
            </label>
          </div>
          <div className={sheetFooterClass}>
            <Button type="button" variant="secondary" onClick={onClose}>Cancel</Button>
            <Button disabled={isSaving || !isValid} type="submit">{isSaving ? 'Saving' : user ? 'Save changes' : 'Create user'}</Button>
          </div>
        </form>
      </section>
    </div>
  )
}
