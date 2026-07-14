import { Boxes, ClipboardCheck, ReceiptText, Sparkles } from 'lucide-react'
import type { ActivityItem } from '@/features/dashboard/types/dashboard'

type RecentActivityProps = { items: ActivityItem[] }

const activityIcons = {
  receipt: ReceiptText,
  sale: Boxes,
  adjustment: ClipboardCheck,
  forecast: Sparkles,
}

export function RecentActivity({ items }: RecentActivityProps) {
  return (
    <section className="rounded-xl border border-border bg-surface p-5 shadow-panel">
      <div>
        <h2 className="text-base font-semibold text-ink">Recent activity</h2>
        <p className="mt-1 text-sm text-muted">Latest posted operational events.</p>
      </div>
      <ol className="mt-5 divide-y divide-border">
        {items.map((item) => {
          const Icon = activityIcons[item.type]

          return (
            <li key={item.id} className="flex gap-3 py-4 first:pt-0 last:pb-0">
              <span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">
                <Icon aria-hidden="true" size={17} />
              </span>
              <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold text-ink">{item.title}</p>
                <p className="mt-1 truncate text-sm text-muted">{item.detail}</p>
              </div>
              <time className="shrink-0 text-xs text-muted">{item.occurredAt}</time>
            </li>
          )
        })}
      </ol>
    </section>
  )
}
