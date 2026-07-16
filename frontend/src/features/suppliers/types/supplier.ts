export type SupplierContact = {
  id: string
  fullName: string
  jobTitle: string | null
  email: string | null
  phone: string | null
  isPrimary: boolean
  isActive: boolean
  version: number
}

export type Supplier = {
  id: string
  code: string
  legalName: string
  taxIdentifier: string | null
  email: string | null
  phone: string | null
  addressLine1: string | null
  addressLine2: string | null
  city: string | null
  province: string | null
  postalCode: string | null
  countryCode: string
  defaultCurrencyCode: string
  isActive: boolean
  contacts: SupplierContact[]
  version: number
}

export type SupplierOption = {
  id: string
  code: string
  legalName: string
  defaultCurrencyCode: string
}

export type SupplierFilters = {
  search: string
  active: 'all' | 'active' | 'inactive'
  page: number
  perPage: number
}

export type SupplierFormValues = {
  code: string
  legalName: string
  taxIdentifier: string
  email: string
  phone: string
  addressLine1: string
  city: string
  province: string
  postalCode: string
  countryCode: string
  defaultCurrencyCode: string
  isActive: boolean
}

export type PaginatedSuppliers = {
  data: Supplier[]
  meta: { page: number; perPage: number; total: number }
}
