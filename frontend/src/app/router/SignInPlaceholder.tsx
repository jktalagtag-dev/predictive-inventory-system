import { LockKeyhole } from 'lucide-react'

export default function SignInPlaceholder() {
  return (
    <div className="w-full max-w-md rounded-xl border border-border bg-surface p-8 shadow-panel">
      <div className="mb-6 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
        <LockKeyhole aria-hidden="true" />
      </div>
      <h1 className="text-2xl font-bold tracking-tight text-ink">Secure access</h1>
      <p className="mt-2 text-sm leading-6 text-muted">
        Authentication screens will be connected to Laravel Sanctum in the identity milestone.
      </p>
    </div>
  )
}
