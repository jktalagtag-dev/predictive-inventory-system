import { apiClient } from '@/shared/api/client'
import type { PaginatedSuppliers, Supplier, SupplierFilters, SupplierFormValues, SupplierOption } from '@/features/suppliers/types/supplier'

type ApiEnvelope<T> = { data: T; meta?: PaginatedSuppliers['meta'] }

export const supplierQueryKeys = {
  lists: () => ['suppliers'] as const,
  list: (filters: SupplierFilters) => ['suppliers', filters] as const,
}

export const supplierOptionQueryKeys = { list: () => ['suppliers', 'options'] as const }

export async function getSuppliers(filters: SupplierFilters): Promise<PaginatedSuppliers> {
  const response = await apiClient.get<ApiEnvelope<Supplier[]>>('/suppliers', {
    params: {
      search: filters.search || undefined,
      isActive: filters.active === 'all' ? undefined : filters.active === 'active',
      page: filters.page,
      perPage: filters.perPage,
    },
  })
  return { data: response.data.data, meta: response.data.meta ?? { page: filters.page, perPage: filters.perPage, total: 0 } }
}

export async function createSupplier(values: SupplierFormValues): Promise<Supplier> {
  const response = await apiClient.post<ApiEnvelope<Supplier>>('/suppliers', {
    code: values.code,
    legalName: values.legalName,
    taxIdentifier: values.taxIdentifier || undefined,
    email: values.email || undefined,
    phone: values.phone || undefined,
    addressLine1: values.addressLine1 || undefined,
    city: values.city || undefined,
    province: values.province || undefined,
    postalCode: values.postalCode || undefined,
    countryCode: values.countryCode,
    defaultCurrencyCode: values.defaultCurrencyCode,
    isActive: values.isActive,
  })
  return response.data.data
}

export async function updateSupplier(supplier: Supplier, values: SupplierFormValues): Promise<Supplier> {
  const response = await apiClient.patch<ApiEnvelope<Supplier>>(`/suppliers/${supplier.id}`, {
    code: values.code,
    legalName: values.legalName,
    taxIdentifier: values.taxIdentifier || null,
    email: values.email || null,
    phone: values.phone || null,
    addressLine1: values.addressLine1 || null,
    city: values.city || null,
    province: values.province || null,
    postalCode: values.postalCode || null,
    countryCode: values.countryCode,
    defaultCurrencyCode: values.defaultCurrencyCode,
    isActive: values.isActive,
    version: supplier.version,
  })
  return response.data.data
}

export async function getSupplierOptions(): Promise<SupplierOption[]> {
  const response = await apiClient.get<ApiEnvelope<Supplier[]>>('/suppliers', { params: { isActive: true, perPage: 100 } })
  return response.data.data.map((supplier) => ({ id: supplier.id, code: supplier.code, legalName: supplier.legalName, defaultCurrencyCode: supplier.defaultCurrencyCode }))
}
