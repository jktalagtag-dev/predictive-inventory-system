import { Link } from 'react-router-dom'
import { Button } from '@/shared/components/Button'

export default function NotFoundPage() {
  return (
    <main className="grid min-h-screen place-items-center bg-canvas p-6">
      <section className="max-w-md text-center">
        <p className="text-sm font-semibold text-brand-700">404</p>
        <h1 className="mt-2 text-3xl font-bold tracking-tight text-ink">Page not found</h1>
        <p className="mt-3 text-sm leading-6 text-muted">The page you requested is unavailable or has moved.</p>
        <Button asChild className="mt-6">
          <Link to="/workspace">Return to workspace</Link>
        </Button>
      </section>
    </main>
  )
}
