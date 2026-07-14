export function PageSkeleton() {
  return (
    <main aria-busy="true" aria-live="polite" className="min-h-screen bg-canvas p-6">
      <span className="sr-only">Loading application</span>
      <div className="mx-auto max-w-7xl space-y-6">
        <div className="h-12 w-72 animate-pulse rounded-lg bg-subtle" />
        <div className="grid gap-4 md:grid-cols-3">
          {[0, 1, 2].map((item) => <div key={item} className="h-40 animate-pulse rounded-xl bg-subtle" />)}
        </div>
      </div>
    </main>
  )
}
