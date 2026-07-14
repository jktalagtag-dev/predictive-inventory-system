import { AlertTriangle, RefreshCw } from 'lucide-react'
import { Button } from '@/shared/components/Button'

type ErrorPanelProps = {
  title: string
  description?: string
  onRetry?: () => void
}

export function ErrorPanel({
  title,
  description = 'Try again. If the problem continues, contact your system administrator with the request ID.',
  onRetry,
}: ErrorPanelProps) {
  return (
    <section aria-labelledby="error-panel-title" className="max-w-lg rounded-xl border border-red-200 bg-surface p-6 shadow-panel">
      <div className="flex items-start gap-3">
        <AlertTriangle aria-hidden="true" className="mt-0.5 text-red-700" size={22} />
        <div>
          <h1 id="error-panel-title" className="text-lg font-bold text-ink">{title}</h1>
          <p className="mt-2 text-sm leading-6 text-muted">{description}</p>
          {onRetry ? (
            <Button className="mt-4" variant="secondary" onClick={onRetry}>
              <RefreshCw aria-hidden="true" size={16} />
              Try again
            </Button>
          ) : null}
        </div>
      </div>
    </section>
  )
}
