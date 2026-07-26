import { type ReactNode } from 'react'
import { createPortal } from 'react-dom'

type PortalProps = {
  children: ReactNode
}

export function Portal({ children }: PortalProps) {
  return createPortal(children, document.body)
}
