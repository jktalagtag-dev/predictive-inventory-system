import { Outlet } from 'react-router-dom'

export function AuthenticationLayout() {
  return (
    <main className="grid min-h-screen place-items-center bg-canvas p-6">
      <Outlet />
    </main>
  )
}
