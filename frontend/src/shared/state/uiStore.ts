import { create } from 'zustand'
import { persist } from 'zustand/middleware'

type Theme = 'light' | 'dark'

type UiState = {
  theme: Theme
  isSidebarOpen: boolean
  isSidebarHoverExpanded: boolean
  // Mobile drawer visibility — deliberately separate from isSidebarOpen
  // (desktop collapse, persisted) so the drawer always starts closed on a
  // fresh mobile load rather than inheriting a persisted desktop preference.
  isMobileNavOpen: boolean
  setTheme: (theme: Theme) => void
  toggleTheme: () => void
  toggleSidebar: () => void
  setSidebarHoverExpanded: (isExpanded: boolean) => void
  openMobileNav: () => void
  closeMobileNav: () => void
  toggleMobileNav: () => void
}

export const useUiStore = create<UiState>()(
  persist(
    (set) => ({
      theme: 'light',
      isSidebarOpen: true,
      isSidebarHoverExpanded: false,
      isMobileNavOpen: false,
      setTheme: (theme) => set({ theme }),
      toggleTheme: () => set((state) => ({ theme: state.theme === 'light' ? 'dark' : 'light' })),
      toggleSidebar: () => set((state) => ({ isSidebarOpen: !state.isSidebarOpen })),
      setSidebarHoverExpanded: (isExpanded) => set({ isSidebarHoverExpanded: isExpanded }),
      openMobileNav: () => set({ isMobileNavOpen: true }),
      closeMobileNav: () => set({ isMobileNavOpen: false }),
      toggleMobileNav: () => set((state) => ({ isMobileNavOpen: !state.isMobileNavOpen })),
    }),
    {
      name: 'predictive-inventory-ui',
      partialize: (state) => ({ theme: state.theme, isSidebarOpen: state.isSidebarOpen }),
    },
  ),
)
