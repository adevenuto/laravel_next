import { beforeEach, describe, expect, it, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

vi.mock('@/lib/api', () => ({
  api: { post: vi.fn(), get: vi.fn() },
  ensureCsrfCookie: vi.fn().mockResolvedValue(undefined),
  resetCsrf: vi.fn(),
  extractMessage: (e: unknown) => (e instanceof Error ? e.message : 'error'),
}))

import { api, ensureCsrfCookie } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('logs the user in and updates state', async () => {
    const fakeUser = {
      id: 1,
      first_name: 'Ada',
      last_name: 'Lovelace',
      email: 'ada@example.com',
    }
    vi.mocked(api.post).mockResolvedValueOnce({ data: fakeUser })

    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(false)

    await store.login({ email: 'ada@example.com', password: 'secret' })

    expect(ensureCsrfCookie).toHaveBeenCalledOnce()
    expect(api.post).toHaveBeenCalledWith('/api/login', {
      email: 'ada@example.com',
      password: 'secret',
    })
    expect(store.user).toEqual(fakeUser)
    expect(store.isAuthenticated).toBe(true)
    expect(store.isLoading).toBe(false)
  })

  it('clears state on logout', async () => {
    vi.mocked(api.post).mockResolvedValueOnce({
      data: { id: 1, first_name: 'A', last_name: 'B', email: 'a@b.com' },
    })
    vi.mocked(api.post).mockResolvedValueOnce({ data: undefined })

    const store = useAuthStore()
    await store.login({ email: 'a@b.com', password: 'x' })
    expect(store.isAuthenticated).toBe(true)

    await store.logout()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })
})
