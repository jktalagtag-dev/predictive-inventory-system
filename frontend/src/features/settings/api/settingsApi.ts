import { apiClient } from '@/shared/api/client'
import type { Setting, UpdateSettingPayload } from '@/features/settings/types/settings'

type ApiEnvelope<T> = { data: T }

export const settingsQueryKeys = {
  list: (branchId: string | null) => ['settings', branchId] as const,
  detail: (key: string, branchId: string | null) => ['settings', 'detail', key, branchId] as const,
}

export async function getSettings(branchId: string | null): Promise<Setting[]> {
  const response = await apiClient.get<ApiEnvelope<Setting[]>>('/settings', { params: { branchId: branchId ?? undefined } })
  return response.data.data
}

export async function updateSetting(key: string, payload: UpdateSettingPayload): Promise<Setting> {
  const response = await apiClient.put<ApiEnvelope<Setting>>(`/settings/${key}`, payload)
  return response.data.data
}
