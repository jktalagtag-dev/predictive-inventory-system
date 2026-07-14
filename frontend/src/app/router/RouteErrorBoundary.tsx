import type { ErrorInfo, PropsWithChildren, ReactNode } from 'react'
import { Component } from 'react'
import { ErrorPanel } from '@/shared/components/ErrorPanel'

type RouteErrorBoundaryState = { hasError: boolean }

export class RouteErrorBoundary extends Component<PropsWithChildren, RouteErrorBoundaryState> {
  public state: RouteErrorBoundaryState = { hasError: false }

  public static getDerivedStateFromError(): RouteErrorBoundaryState {
    return { hasError: true }
  }

  public componentDidCatch(_error: Error, _errorInfo: ErrorInfo): void {
    // A production error-reporting adapter belongs here; no sensitive payload is captured.
  }

  public render(): ReactNode {
    if (this.state.hasError) {
      return <ErrorPanel title="This page could not be loaded" onRetry={() => this.setState({ hasError: false })} />
    }

    return this.props.children
  }
}
