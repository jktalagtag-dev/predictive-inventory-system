import { cn } from '@/shared/lib/cn'

/**
 * Shared responsive chrome for modals and drawers so every dialog gets
 * the same mobile treatment without 30 divergent copies of the class
 * strings. On mobile (< sm) forms and detail drawers become full-screen
 * slide-up / edge-to-edge sheets; at sm+ they render as the centered
 * card / right-side drawer the desktop UI has always used
 * (CLAUDE.md sections 31, 34).
 */

/** Overlay: bottom-anchored full-screen on mobile, centered at sm+. */
export const modalOverlayClass =
  'fixed inset-0 z-50 flex flex-col justify-end bg-slate-950/40 p-0 sm:items-center sm:justify-center sm:p-4'

/** Panel: full-screen sheet on mobile, centered card at sm+. Pass the sm:max-w-* size. */
export function modalPanelClass(sizeMax: string, className?: string): string {
  return cn(
    'flex max-h-dvh w-full flex-col overflow-hidden rounded-none border-0 bg-surface shadow-panel',
    'sm:max-h-[90vh] sm:rounded-card sm:border sm:border-border',
    sizeMax,
    className,
  )
}

/** Right-side drawer: full-screen on mobile, capped right panel at sm+. */
export function drawerPanelClass(sizeMax = 'sm:max-w-2xl', className?: string): string {
  return cn(
    'ml-auto flex h-dvh w-full flex-col border-l border-border bg-surface shadow-panel',
    sizeMax,
    className,
  )
}

/** Sticky sheet header (title + close). */
export const sheetHeaderClass =
  'sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-border bg-surface px-5 py-4 sm:px-8 sm:py-6'

/** Scrollable sheet body. */
export const sheetBodyClass = 'min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-8 sm:py-6'

/** Sticky sheet footer (actions), safe-area aware. */
export const sheetFooterClass =
  'sticky bottom-0 z-10 flex flex-wrap justify-end gap-3 border-t border-border bg-surface px-5 py-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:px-8 sm:pb-4'
