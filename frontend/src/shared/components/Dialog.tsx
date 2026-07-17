import { type PropsWithChildren, type ReactNode, useEffect, useId, useRef } from 'react'
import { X } from 'lucide-react'
import { cn } from '@/shared/lib/cn'
import { Button } from '@/shared/components/Button'

const FOCUSABLE_SELECTOR = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'

const sizeClasses = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-3xl',
}

type DialogProps = {
  title: string
  description?: string
  onClose: () => void
  size?: keyof typeof sizeClasses
  footer?: ReactNode
}

/**
 * Shared modal chrome implementing CLAUDE.md section 34's requirements
 * that no per-feature dialog was previously doing on its own: Escape
 * closes, focus is trapped inside while open, and focus returns to
 * whatever opened it on close.
 */
export function Dialog({ title, description, onClose, size = 'md', footer, children }: PropsWithChildren<DialogProps>) {
  const titleId = useId()
  const panelRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const previouslyFocused = document.activeElement as HTMLElement | null;
    const panel = panelRef.current;
    const firstFocusable = panel?.querySelector<HTMLElement>(FOCUSABLE_SELECTOR);
    (firstFocusable ?? panel)?.focus();

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onClose();
        return;
      }

      if (event.key !== 'Tab' || !panel) return;

      const focusable = Array.from(panel.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR));
      if (focusable.length === 0) return;

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      previouslyFocused?.focus();
    };
  }, [onClose]);

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4" role="presentation" onMouseDown={onClose}>
      <section
        ref={panelRef}
        aria-describedby={description ? `${titleId}-description` : undefined}
        aria-labelledby={titleId}
        aria-modal="true"
        className={cn('max-h-[90vh] w-full overflow-y-auto rounded-xl border border-border bg-surface p-6 shadow-panel', sizeClasses[size])}
        role="dialog"
        tabIndex={-1}
        onMouseDown={(event) => event.stopPropagation()}
      >
        <div className="flex items-start justify-between gap-4">
          <div>
            <h2 id={titleId} className="text-lg font-bold text-ink">{title}</h2>
            {description ? <p id={`${titleId}-description`} className="mt-1 text-sm text-muted">{description}</p> : null}
          </div>
          <Button aria-label="Close dialog" size="icon" variant="ghost" onClick={onClose}>
            <X aria-hidden="true" size={18} />
          </Button>
        </div>

        <div className="mt-6">{children}</div>

        {footer ? <div className="mt-6 flex justify-end gap-3 border-t border-border pt-5">{footer}</div> : null}
      </section>
    </div>
  )
}
