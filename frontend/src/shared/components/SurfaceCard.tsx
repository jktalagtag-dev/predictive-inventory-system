import type { PropsWithChildren, ReactNode } from 'react'

type SurfaceCardProps = PropsWithChildren<{
  title: string
  icon?: ReactNode
}>

export function SurfaceCard({ title, icon, children }: SurfaceCardProps) {
  return (
    <section className="rounded-card border border-border bg-surface p-8 shadow-panel">
      <div className="flex items-center gap-3">
        {icon ? <span className="grid h-10 w-10 place-items-center rounded-lg bg-brand-50 text-brand-700">{icon}</span> : null}
        <h2 className="text-base font-semibold text-ink">{title}</h2>
      </div>
      <p className="mt-4 text-sm leading-6 text-muted">{children}</p>
    </section>
  )
}
