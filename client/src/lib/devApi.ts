// DEV-UNLOCK: dev-only progress shortcuts. The backend route group is gated to
// APP_ENV ∈ {local, testing}; in production these endpoints don't exist (404).
import { api, ensureCsrfCookie } from '@/lib/api'

export interface DevToggleResponse {
  state: 'unlocked' | 'normal'
  xp_total: number
  vocab_total: number
  completed_count?: number
  restored_count?: number
  reset?: boolean
}

export async function toggleCompleteAll(): Promise<DevToggleResponse> {
  await ensureCsrfCookie()
  const { data } = await api.post<DevToggleResponse>('/api/dev/progress/toggle-complete-all')
  return data
}

export async function resetProgress(): Promise<DevToggleResponse> {
  await ensureCsrfCookie()
  const { data } = await api.post<DevToggleResponse>('/api/dev/progress/reset')
  return data
}
