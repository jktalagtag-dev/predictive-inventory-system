import { cn } from '@/shared/lib/cn'

const sizeClasses = {
  sm: 'h-8 w-8 text-xs',
  md: 'h-10 w-10 text-sm',
  lg: 'h-14 w-14 text-lg',
}

// A small, fixed palette keeps initials-fallback avatars legible against
// both light and dark surfaces without needing per-theme color math.
const PALETTE = ['bg-rose-500', 'bg-amber-500', 'bg-emerald-500', 'bg-sky-500', 'bg-violet-500', 'bg-fuchsia-500', 'bg-cyan-500']

function initialsFor(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0]!.slice(0, 2).toUpperCase()
  return (parts[0]![0]! + parts[parts.length - 1]![0]!).toUpperCase()
}

function colorFor(name: string): string {
  let hash = 0
  for (let i = 0; i < name.length; i++) {
    hash = (hash * 31 + name.charCodeAt(i)) >>> 0
  }
  return PALETTE[hash % PALETTE.length]!
}

type AvatarProps = {
  name: string
  src?: string | null
  size?: keyof typeof sizeClasses
  className?: string
}

export function Avatar({ name, src, size = 'md', className }: AvatarProps) {
  if (src) {
    return (
      <img
        alt={name}
        className={cn('rounded-full object-cover', sizeClasses[size], className)}
        src={src}
      />
    )
  }

  return (
    <span
      aria-hidden="true"
      className={cn('flex items-center justify-center rounded-full font-semibold text-white', sizeClasses[size], colorFor(name), className)}
    >
      {initialsFor(name)}
    </span>
  )
}
