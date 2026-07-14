import { Building2, Layers3 } from 'lucide-react'
import { PageHeader } from '@/shared/components/PageHeader'
import { SurfaceCard } from '@/shared/components/SurfaceCard'

export default function WorkspacePlaceholder() {
  return (
    <div className="space-y-6">
      <PageHeader
        title="Workspace"
        description="The shared application foundation is ready for feature modules."
      />
      <div className="grid gap-4 md:grid-cols-2">
        <SurfaceCard icon={<Layers3 aria-hidden="true" />} title="Feature-first architecture">
          New operational modules will own their pages, typed API services, hooks, schemas, and tests.
        </SurfaceCard>
        <SurfaceCard icon={<Building2 aria-hidden="true" />} title="Branch-aware workspace">
          Branch selection, permissions, and session data will be connected before business data is introduced.
        </SurfaceCard>
      </div>
    </div>
  )
}
