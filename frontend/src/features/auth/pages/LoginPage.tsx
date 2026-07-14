import { LockKeyhole } from 'lucide-react'
import { LoginForm } from '@/features/auth/components/LoginForm'

export default function LoginPage() {
  return (
    <div className="w-full max-w-md">
      <div className="mb-6 flex items-center gap-3">
        <div className="grid h-12 w-12 place-items-center rounded-xl bg-brand-600 text-white shadow-panel">
          <LockKeyhole aria-hidden="true" size={22} />
        </div>
        <div>
          <p className="text-sm font-semibold text-ink">Predictive Inventory System</p>
          <p className="text-xs text-muted">Steven Hydrotech Exponent</p>
        </div>
      </div>
      <LoginForm />
    </div>
  )
}
