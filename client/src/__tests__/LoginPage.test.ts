import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import { createRouter, createMemoryHistory } from 'vue-router'
import LoginPage from '@/pages/LoginPage.vue'

vi.mock('@/lib/api', () => ({
  api: { post: vi.fn(), get: vi.fn() },
  ensureCsrfCookie: vi.fn().mockResolvedValue(undefined),
  resetCsrf: vi.fn(),
  extractMessage: (e: unknown) => (e instanceof Error ? e.message : 'error'),
}))

const router = createRouter({
  history: createMemoryHistory(),
  routes: [
    { path: '/login', name: 'login', component: { template: '<div />' } },
    { path: '/signup', name: 'signup', component: { template: '<div />' } },
    { path: '/forgot-password', name: 'forgot-password', component: { template: '<div />' } },
    { path: '/dashboard', name: 'dashboard', component: { template: '<div />' } },
  ],
})

describe('LoginPage', () => {
  it('renders email + password inputs and submit button', async () => {
    router.push('/login')
    await router.isReady()

    const wrapper = mount(LoginPage, {
      global: {
        plugins: [createTestingPinia({ createSpy: vi.fn }), router],
      },
    })

    expect(wrapper.find('#email').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').text()).toContain('Sign in')
    expect(wrapper.text()).toContain('Forgot password?')
  })
})
