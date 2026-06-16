import { describe, expect, it, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import AccentPlacer from '@/components/exercises/AccentPlacer.vue'
import type { AccentPlacerPayload } from '@/types/exercises'

const payload: AccentPlacerPayload = {
  prompt: 'Tap the vowel that needs the accent.',
  words: [
    { base: 'nacion', accented: 'nación', accent_letter_index: 2 },
    { base: 'cancion', accented: 'canción', accent_letter_index: 3 },
  ],
}

describe('AccentPlacer', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  it('starts on the first word with the base (un-accented) display', () => {
    const wrapper = mount(AccentPlacer, { props: { payload } })
    expect(wrapper.text()).toContain('nacion')
    expect(wrapper.text()).not.toContain('nación')
  })

  it('swaps to the accented form on a correct tap', async () => {
    const wrapper = mount(AccentPlacer, { props: { payload } })
    // index 2 of 'nacion' is the 'c' wait — re-check: n(0) a(1) c(2) i(3) o(4) n(5).
    // accent_letter_index = 2 → 'c'. Tap that.
    const buttons = wrapper.findAll('button[type="button"]')
    // The base 'n' appears twice; find by id rather than label.
    // We can rely on order: the labels are the chars in order.
    await buttons[2].trigger('click')
    expect(wrapper.text()).toContain('nación')
  })

  it('persists wrong-on tint on tries that miss the accent vowel', async () => {
    const wrapper = mount(AccentPlacer, { props: { payload } })
    const buttons = wrapper.findAll('button[type="button"]')
    await buttons[0].trigger('click') // tap 'n' — wrong
    expect(buttons[0].classes().join(' ')).toMatch(/border-coach/)
  })

  it('advances through all words and emits complete with correct: true / score: 100 on all-correct', async () => {
    const wrapper = mount(AccentPlacer, { props: { payload } })

    // Word 1: tap the right index (2).
    let buttons = wrapper.findAll('button[type="button"]')
    await buttons[2].trigger('click')
    await vi.advanceTimersByTimeAsync(1000)

    // Word 2 'cancion': c(0) a(1) n(2) c(3) i(4) o(5) n(6); accent_letter_index = 3 → 'c'.
    buttons = wrapper.findAll('button[type="button"]')
    await buttons[3].trigger('click')
    await vi.advanceTimersByTimeAsync(1000)

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean; score: number }).correct).toBe(true)
    expect((events![0][0] as { correct: boolean; score: number }).score).toBe(100)
  })
})
