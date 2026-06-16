import { describe, expect, it, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import TrapOrTreat from '@/components/exercises/TrapOrTreat.vue'
import type { TrapOrTreatPayload } from '@/types/exercises'

const payload: TrapOrTreatPayload = {
  prompt: 'Clean conversion or rebel word?',
  rounds: [
    {
      en: 'translation',
      naive_es: 'translación',
      real_es: 'traducción',
      is_exception: true,
      rebel_word_key: 'traduccion',
      explanation: 'From traducir.',
    },
    {
      en: 'celebration',
      naive_es: 'celebración',
      real_es: 'celebración',
      is_exception: false,
      explanation: 'Clean swap.',
    },
  ],
}

describe('TrapOrTreat', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  it('captures the rebel_word_key when an exception round is picked correctly', async () => {
    const wrapper = mount(TrapOrTreat, { props: { payload } })

    // Round 1: pick traducción (the real, the rebel).
    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('traducción'))!
      .trigger('click')

    // The "Rebel captured" toast should render.
    expect(wrapper.text()).toContain('Rebel captured')
    expect(wrapper.text()).toContain('traducción')
  })

  it('flushes the rebels_captured meta on final complete', async () => {
    const wrapper = mount(TrapOrTreat, { props: { payload } })

    // Round 1: pick rebel correctly.
    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('traducción'))!
      .trigger('click')
    await vi.advanceTimersByTimeAsync(1500)

    // Round 2: the two options for celebration are identical (naive === real).
    // The MultipleChoiceBase treats them as separate entries by index but compares
    // by value equality. Pick either — both should resolve to correctValue.
    const buttons = wrapper.findAll('button')
    const celebrationBtn = buttons.find(
      (b) => b.text().includes('celebración') && b.element.disabled === false
    )!
    await celebrationBtn.trigger('click')
    await vi.advanceTimersByTimeAsync(1500)

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    const result = events![0][0] as {
      correct: boolean
      score: number
      meta?: Record<string, unknown>
    }
    expect(result.meta?.rebels_captured).toEqual(['traduccion'])
  })

  it('shows the explanation as coach text when the naïve form is tapped', async () => {
    const wrapper = mount(TrapOrTreat, { props: { payload } })

    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('translación'))!
      .trigger('click')

    expect(wrapper.text()).toContain('From traducir.')
  })

  it('does not capture a rebel for non-exception rounds', async () => {
    const singleClean: TrapOrTreatPayload = {
      prompt: 'Clean only.',
      rounds: [
        {
          en: 'celebration',
          naive_es: 'celebrasión',
          real_es: 'celebración',
          is_exception: false,
          explanation: 'Clean swap.',
        },
      ],
    }
    const wrapper = mount(TrapOrTreat, { props: { payload: singleClean } })

    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('celebración'))!
      .trigger('click')
    await vi.advanceTimersByTimeAsync(1500)

    expect(wrapper.text()).not.toContain('Rebel captured')
    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    const result = events![0][0] as { meta?: Record<string, unknown> }
    expect(result.meta?.rebels_captured).toEqual([])
  })
})
