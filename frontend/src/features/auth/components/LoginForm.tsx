import { type FormEvent, useState } from 'react'
import { AlertCircle, LoaderCircle } from 'lucide-react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { authQueryKeys, signIn } from '@/features/auth/api/authApi'
import type { LoginCredentials } from '@/features/auth/types/auth'
import { type ApiError } from '@/shared/api/client'
import { Button } from '@/shared/components/Button'

const initialCredentials: LoginCredentials = { email: '', password: '', remember: false }

export function LoginForm() {
  const navigate = useNavigate()
  const location = useLocation()
  const queryClient = useQueryClient()
  const [credentials, setCredentials] = useState<LoginCredentials>(initialCredentials)

  const loginMutation = useMutation({
    mutationFn: signIn,
    onSuccess: (session) => {
      queryClient.setQueryData(authQueryKeys.session, session)
      const returnPath = typeof location.state === 'object'
        && location.state !== null
        && 'from' in location.state
        && typeof location.state.from === 'object'
        && location.state.from !== null
        && 'pathname' in location.state.from
        && typeof location.state.from.pathname === 'string'
        && location.state.from.pathname.startsWith('/')
        ? location.state.from.pathname
        : '/dashboard'

      navigate(returnPath, { replace: true })
    },
  })

  const error = loginMutation.error as ApiError | null
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    loginMutation.mutate(credentials)
  }

  return (
    <form className="w-full max-w-md rounded-xl border border-border bg-surface p-8 shadow-panel" onSubmit={submit}>
      <h1 className="text-2xl font-bold tracking-tight text-ink">Sign in</h1>
      <p className="mt-2 text-sm leading-6 text-muted">Use your authorized account to access the inventory workspace.</p>

      {error ? (
        <div className="mt-5 flex gap-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
          <AlertCircle aria-hidden="true" className="mt-0.5 shrink-0" size={18} />
          <div>
            <p>{error.message}</p>
            {error.requestId ? <p className="mt-1 text-xs">Request ID: {error.requestId}</p> : null}
          </div>
        </div>
      ) : null}

      <div className="mt-6 space-y-4">
        <label className="block text-sm font-semibold text-ink">
          Email address
          <input
            autoComplete="email"
            className="mt-2 h-11 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
            name="email"
            onChange={(event) => setCredentials((state) => ({ ...state, email: event.target.value }))}
            required
            type="email"
            value={credentials.email}
          />
        </label>
        <label className="block text-sm font-semibold text-ink">
          Password
          <input
            autoComplete="current-password"
            className="mt-2 h-11 w-full rounded-lg border border-border bg-surface px-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20"
            name="password"
            onChange={(event) => setCredentials((state) => ({ ...state, password: event.target.value }))}
            required
            type="password"
            value={credentials.password}
          />
        </label>
        <label className="flex items-center gap-2 text-sm text-muted">
          <input
            checked={credentials.remember}
            className="h-4 w-4 rounded border-border text-brand-600 focus:ring-brand-600"
            name="remember"
            onChange={(event) => setCredentials((state) => ({ ...state, remember: event.target.checked }))}
            type="checkbox"
          />
          Keep me signed in on this device
        </label>
      </div>

      <Button className="mt-6 w-full" disabled={loginMutation.isPending} type="submit">
        {loginMutation.isPending ? <LoaderCircle aria-hidden="true" className="animate-spin" size={18} /> : null}
        {loginMutation.isPending ? 'Signing in' : 'Sign in'}
      </Button>
    </form>
  )
}
