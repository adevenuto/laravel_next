import axios, { AxiosError } from 'axios'
import type { ApiErrorBody } from '@/types/user'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

let csrfPromise: Promise<void> | null = null

export function ensureCsrfCookie(): Promise<void> {
  if (!csrfPromise) {
    csrfPromise = api
      .get('/sanctum/csrf-cookie')
      .then(() => undefined)
      .catch((err: unknown) => {
        csrfPromise = null
        throw err
      })
  }
  return csrfPromise
}

export function resetCsrf(): void {
  csrfPromise = null
}

export function extractMessage(err: unknown): string {
  if (err instanceof AxiosError) {
    const data = err.response?.data as ApiErrorBody | undefined
    if (data?.errors) {
      const firstField = Object.values(data.errors)[0]
      if (firstField && firstField.length > 0) return firstField[0] ?? 'Request failed'
    }
    if (data?.message) return data.message
    if (err.message) return err.message
  }
  if (err instanceof Error) return err.message
  return 'Something went wrong. Please try again.'
}

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    if (error.response?.status === 401) {
      resetCsrf()
      const { useAuthStore } = await import('@/stores/auth')
      const auth = useAuthStore()
      auth.clear()
      const { default: router } = await import('@/router')
      if (router.currentRoute.value.meta.requiresAuth) {
        router.push({ name: 'login' })
      }
    }
    return Promise.reject(error)
  }
)
