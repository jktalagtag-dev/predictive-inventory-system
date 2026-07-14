import type { PropsWithChildren } from 'react'
import { QueryProvider } from '@/app/providers/QueryProvider'
import { ThemeProvider } from '@/app/providers/ThemeProvider'
import { AppErrorBoundary } from '@/shared/components/AppErrorBoundary'

export function AppProviders({ children }: PropsWithChildren) {
  return (
    <AppErrorBoundary>
      <ThemeProvider>
        <QueryProvider>{children}</QueryProvider>
      </ThemeProvider>
    </AppErrorBoundary>
  )
}
