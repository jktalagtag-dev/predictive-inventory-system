import { type PropsWithChildren, createContext, useCallback, useContext, useMemo, useState } from 'react'
import { CheckCircle2, CircleAlert, Info, X } from 'lucide-react'
import { cn } from '@/shared/lib/cn'

export type ToastVariant = 'success' | 'error' | 'info'

type ToastInput = {
  title: string
  description?: string
  variant?: ToastVariant
}

type ToastItem = ToastInput & { id: string; variant: ToastVariant }

type ToastContextValue = {
  toast: (input: ToastInput) => void
}

const ToastContext = createContext<ToastContextValue | null>(null)

const variantClasses: Record<ToastVariant, string> = {
  success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
  error: 'border-red-200 bg-red-50 text-red-800',
  info: 'border-blue-200 bg-blue-50 text-blue-800',
}

const variantIcons: Record<ToastVariant, typeof Info> = {
  success: CheckCircle2,
  error: CircleAlert,
  info: Info,
}

const AUTO_DISMISS_MS = 5000

/**
 * Brief, non-blocking confirmations and notices (CLAUDE.md section 39).
 * Never the only place critical or lengthy information appears — pair
 * with inline messaging for anything a user must not miss. Duplicate
 * title+description toasts are coalesced instead of stacking, so a
 * flood of identical events (e.g. batch sync results) can't spam the
 * viewport.
 */
export function ToastProvider({ children }: PropsWithChildren) {
  const [toasts, setToasts] = useState<ToastItem[]>([])

  const dismiss = useCallback((id: string) => {
    setToasts((current) => current.filter((item) => item.id !== id))
  }, [])

  const toast = useCallback((input: ToastInput) => {
    setToasts((current) => {
      const isDuplicate = current.some((item) => item.title === input.title && item.description === input.description)
      if (isDuplicate) return current

      const id = crypto.randomUUID()
      setTimeout(() => dismiss(id), AUTO_DISMISS_MS)

      return [...current, { id, variant: 'info', ...input }]
    })
  }, [dismiss])

  const value = useMemo<ToastContextValue>(() => ({ toast }), [toast])

  return (
    <ToastContext.Provider value={value}>
      {children}
      <div aria-atomic="false" aria-live="polite" className="pointer-events-none fixed bottom-4 right-4 z-[100] flex w-full max-w-sm flex-col gap-2">
        {toasts.map((item) => {
          const Icon = variantIcons[item.variant]

          return (
            <div key={item.id} className={cn('pointer-events-auto rounded-lg border p-4 shadow-panel', variantClasses[item.variant])} role="status">
              <div className="flex items-start gap-3">
                <Icon aria-hidden="true" className="mt-0.5 shrink-0" size={18} />
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-semibold">{item.title}</p>
                  {item.description ? <p className="mt-1 text-sm opacity-90">{item.description}</p> : null}
                </div>
                <button aria-label="Dismiss notification" className="shrink-0 opacity-70 hover:opacity-100" type="button" onClick={() => dismiss(item.id)}>
                  <X aria-hidden="true" size={16} />
                </button>
              </div>
            </div>
          )
        })}
      </div>
    </ToastContext.Provider>
  )
}

export function useToast(): ToastContextValue {
  const context = useContext(ToastContext)

  if (!context) {
    throw new Error('useToast must be used within ToastProvider')
  }

  return context
}
