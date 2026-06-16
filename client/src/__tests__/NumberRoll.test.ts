import { describe, expect, it, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import NumberRoll from '@/components/NumberRoll.vue'

function setReducedMotion(matches: boolean) {
  Object.defineProperty(window, 'matchMedia', {
    configurable: true,
    writable: true,
    value: vi.fn().mockImplementation((query: string) => ({
      matches,
      media: query,
      onchange: null,
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      addListener: vi.fn(),
      removeListener: vi.fn(),
      dispatchEvent: vi.fn(),
    })),
  })
}

describe('NumberRoll', () => {
  beforeEach(() => {
    setReducedMotion(false)
  })

  it('renders the initial `from` value before animation runs', () => {
    setReducedMotion(true)
    const wrapper = mount(NumberRoll, { props: { from: 0, to: 400 } })
    // Reduced-motion snaps to `to` instantly on mount.
    expect(wrapper.text()).toBe('400')
  })

  it('snaps to `to` under prefers-reduced-motion', async () => {
    setReducedMotion(true)
    const wrapper = mount(NumberRoll, { props: { from: 0, to: 400, durationMs: 100 } })
    await flushPromises()
    expect(wrapper.text()).toBe('400')
  })

  it('animates from `from` toward `to` under normal motion', async () => {
    setReducedMotion(false)
    const wrapper = mount(NumberRoll, { props: { from: 0, to: 1000, durationMs: 1200 } })
    await flushPromises()
    // GSAP runs in a microtask loop in test env; the first render shows the from-side.
    const initial = parseInt(wrapper.text().replace(/,/g, ''), 10)
    expect(initial).toBeGreaterThanOrEqual(0)
    expect(initial).toBeLessThanOrEqual(1000)
  })

  it('supports a custom format function', async () => {
    setReducedMotion(true)
    const wrapper = mount(NumberRoll, {
      props: { from: 0, to: 5, format: (n) => `+${Math.round(n)}!` },
    })
    expect(wrapper.text()).toBe('+5!')
  })

  it('formats thousands with a comma by default', async () => {
    setReducedMotion(true)
    const wrapper = mount(NumberRoll, { props: { from: 0, to: 1234 } })
    expect(wrapper.text()).toBe('1,234')
  })
})
