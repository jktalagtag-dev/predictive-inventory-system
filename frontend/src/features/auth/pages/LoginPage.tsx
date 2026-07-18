import { LoginForm } from '@/features/auth/components/LoginForm'

export default function LoginPage() {
  return (
    <div className="w-full max-w-md">
      <div className="mb-6">
        <h1 className="text-2xl font-bold tracking-tight text-ink">Welcome back</h1>
        <p className="mt-2 text-sm leading-6 text-muted">
          Sign in to manage inventory, purchase orders, and demand forecasts for Steven Hydrotech Exponent.
        </p>
      </div>
      <LoginForm />
    </div>
  )
}
