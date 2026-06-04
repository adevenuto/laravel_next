import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api, ensureCsrfCookie, resetCsrf } from '@/lib/api'
import type { LoginPayload, RegisterPayload, ResetPasswordPayload, User } from '@/types/user'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isLoading = ref(false)

  const isAuthenticated = computed(() => user.value !== null)

  function clear() {
    user.value = null
  }

  async function fetchUser() {
    try {
      const { data } = await api.get<User>('/api/user')
      user.value = data
    } catch {
      user.value = null
    }
  }

  async function login(payload: LoginPayload) {
    isLoading.value = true
    try {
      await ensureCsrfCookie()
      const { data } = await api.post<{ user: User } | User>('/api/login', payload)
      user.value =
        'user' in (data as { user?: User }) ? (data as { user: User }).user : (data as User)
    } finally {
      isLoading.value = false
    }
  }

  async function register(payload: RegisterPayload) {
    isLoading.value = true
    try {
      await ensureCsrfCookie()
      const { data } = await api.post<{ user: User } | User>('/api/register', payload)
      user.value =
        'user' in (data as { user?: User }) ? (data as { user: User }).user : (data as User)
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    isLoading.value = true
    try {
      await api.post('/api/logout').catch(() => {})
    } finally {
      clear()
      resetCsrf()
      isLoading.value = false
    }
  }

  async function forgotPassword(email: string) {
    isLoading.value = true
    try {
      await ensureCsrfCookie()
      await api.post('/api/password-reset', { email })
    } finally {
      isLoading.value = false
    }
  }

  async function resetPassword(payload: ResetPasswordPayload) {
    isLoading.value = true
    try {
      await ensureCsrfCookie()
      await api.post('/api/password-reset/confirm', payload)
    } finally {
      isLoading.value = false
    }
  }

  return {
    user,
    isLoading,
    isAuthenticated,
    clear,
    fetchUser,
    login,
    register,
    logout,
    forgotPassword,
    resetPassword,
  }
})
