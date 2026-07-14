import { apiClient, sessionClient } from '@/shared/api/client'
import type { AuthSession, LoginCredentials } from '@/features/auth/types/auth'

type ApiEnvelope<T> = { data: T }

export const authQueryKeys = {
  session: ['auth', 'session'] as const,
}

export async function requestCsrfCookie(): Promise<void> {
  await sessionClient.get('/sanctum/csrf-cookie')
}

export async function getCurrentSession(): Promise<AuthSession> {
  const response = await apiClient.get<ApiEnvelope<AuthSession>>('/auth/me')
  return response.data.data
}

export async function signIn(credentials: LoginCredentials): Promise<AuthSession> {
  await requestCsrfCookie()
  const response = await apiClient.post<ApiEnvelope<AuthSession>>('/auth/login', credentials)
  return response.data.data
}

export async function signOut(): Promise<void> {
  await apiClient.post('/auth/logout')
}
