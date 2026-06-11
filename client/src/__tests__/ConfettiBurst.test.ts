import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ConfettiBurst from '@/components/ConfettiBurst.vue'

function stubMatchMedia(reduced: boolean) {
  vi.stubGlobal('matchMedia', (query: string) => ({
    matches: query.includes('prefers-reduced-motion') ? reduced : false,
    media: query,
    onchange: null,
    addEventListener: () => {},
    removeEventListener: () => {},
    addListener: () => {},
    removeListener: () => {},
    dispatchEvent: () => false,
  }))
}

describe('ConfettiBurst', () => {
  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('renders no pieces when active is false', async () => {
    stubMatchMedia(false)
    const wrapper = mount(ConfettiBurst, {
      props: { active: false, count: 10, duration: 500 },
      attachTo: document.body,
    })
    await flushPromises()
    expect(document.querySelectorAll('.confetti-piece').length).toBe(0)
    wrapper.unmount()
  })

  it('renders the requested number of pieces and emits done after duration', async () => {
    stubMatchMedia(false)
    vi.useFakeTimers()
    const wrapper = mount(ConfettiBurst, {
      props: { active: true, count: 12, duration: 1000 },
      attachTo: document.body,
    })
    await flushPromises()

    expect(document.querySelectorAll('.confetti-piece').length).toBe(12)

    vi.advanceTimersByTime(1100)
    await flushPromises()

    expect(wrapper.emitted('done')).toBeTruthy()
    expect(document.querySelectorAll('.confetti-piece').length).toBe(0)
    wrapper.unmount()
  })

  it('renders nothing and emits done immediately when prefers-reduced-motion is set', async () => {
    stubMatchMedia(true)
    const wrapper = mount(ConfettiBurst, {
      props: { active: true, count: 30, duration: 3000 },
      attachTo: document.body,
    })
    await flushPromises()

    expect(document.querySelectorAll('.confetti-piece').length).toBe(0)
    expect(wrapper.emitted('done')).toBeTruthy()
    wrapper.unmount()
  })
})
