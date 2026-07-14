import type { PropsWithChildren } from 'react'
import { useEffect } from 'react'
import { useUiStore } from '@/shared/state/uiStore'

export function ThemeProvider({ children }: PropsWithChildren) {
  const theme = useUiStore((state) => state.theme)

  useEffect(() => {
    document.documentElement.dataset.theme = theme
  }, [theme])

  return <>{children}</>
}
