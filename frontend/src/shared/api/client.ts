import axios, { type AxiosError } from 'axios'

export type ApiError = {
  code: string
  message: string
  status?: number
  requestId?: string
  details?: Record<string, string[]>
}

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
  withCredentials: true,
  headers: { Accept: 'application/json' },
})

apiClient.interceptors.request.use((config) => {
  config.headers.set('X-Request-ID', crypto.randomUUID())
  return config
})

apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ error?: Omit<ApiError, 'status'> }>) => {
    const apiError: ApiError = {
      code: error.response?.data?.error?.code ?? 'NETWORK_ERROR',
      message: error.response?.data?.error?.message ?? 'The request could not be completed.',
      status: error.response?.status,
      requestId: error.response?.data?.error?.requestId,
      details: error.response?.data?.error?.details,
    }

    return Promise.reject(apiError)
  },
)
