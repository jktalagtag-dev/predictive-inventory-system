export type SettingValueType = 'string' | 'integer' | 'decimal' | 'boolean' | 'json' | 'date'

export type Setting = {
  key: string
  branchId: string | null
  valueType: SettingValueType
  value: string | number | boolean | Record<string, unknown> | null
  isSensitive: boolean
  isRedacted: boolean
  ownerOnly: boolean
  description: string
  version: number
  isOverridden: boolean
}

export type UpdateSettingPayload = {
  branchId?: string | null
  valueType: SettingValueType
  value: string | number | boolean
  version: number
}
