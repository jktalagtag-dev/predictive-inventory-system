import type { ErrorInfo, PropsWithChildren, ReactNode } from 'react'
import { Component } from 'react'
import { ErrorPanel } from '@/shared/components/ErrorPanel'

type AppErrorBoundaryState = { hasError: boolean }

export class AppErrorBoundary extends Component<PropsWithChildren, AppErrorBoundaryState> {
  public state: AppErrorBoundaryState = { hasError: false }

  public static getDerivedStateFromError(): AppErrorBoundaryState {
    return { hasError: true }
  }

  public componentDidCatch(_error: Error, _errorInfo: ErrorInfo): void {
    // Integrate approved client error reporting here without capturing sensitive values.
  }

  public render(): ReactNode {
    if (this.state.hasError) {
      return (
        <main className="grid min-h-screen place-items-center bg-canvas p-6">
          <ErrorPanel title="The application could not start" onRetry={() => this.setState({ hasError: false })} />
        </main>
      )
    }

    return this.props.children
  }
}
