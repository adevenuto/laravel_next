import { describe, expect, it, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import WordForge from '@/components/exercises/WordForge.vue'
import type { WordForgePayload } from '@/types/exercises'

function basePayload(words: WordForgePayload['words']): WordForgePayload {
  return {
    prompt: 'Forge the Spanish twin.',
    rule_key: 'tion_cion',
    transformation_hint: 'tion → ción',
    words,
  }
}

async function typeInto(wrapper: ReturnType<typeof mount>, value: string) {
  const input = wrapper.find('input')
  await input.setValue(value)
  await flushPromises()
}

describe('WordForge', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  it('colors each slot based on strict-prefix match', async () => {
    const wrapper = mount(WordForge, {
      props: {
        payload: basePayload([{ en: 'nation', es: 'nación', audio_key: 'k' }]),
      },
    })

    await typeInto(wrapper, 'nac')
    // First 3 slots are typed correctly → success border.
    const slots = wrapper.findAll('span.es.inline-flex')
    expect(slots[0].classes().join(' ')).toMatch(/border-success/)
    expect(slots[1].classes().join(' ')).toMatch(/border-success/)
    expect(slots[2].classes().join(' ')).toMatch(/border-success/)
    // Untouched slots stay idle.
    expect(slots[3].classes().join(' ')).toMatch(/border-border/)
  })

  it('shows the coach line on a wrong character', async () => {
    const wrapper = mount(WordForge, {
      props: {
        payload: basePayload([{ en: 'nation', es: 'nación', audio_key: 'k' }]),
      },
    })

    await typeInto(wrapper, 'nax')
    expect(wrapper.text()).toMatch(/Almost — that letter does not match/i)
  })

  it('uses the seeded tweak_note as the coach line on a wrong char when present', async () => {
    const wrapper = mount(WordForge, {
      props: {
        payload: basePayload([
          {
            en: 'station',
            es: 'estación',
            audio_key: 'k',
            tweak_note: 'Spanish adds e- before st-.',
          },
        ]),
      },
    })

    await typeInto(wrapper, 's') // wrong — should be 'e' first
    expect(wrapper.text()).toContain('Spanish adds e- before st-.')
  })

  it('emits complete with correct: true and score: 100 after forging all words', async () => {
    const wrapper = mount(WordForge, {
      props: {
        payload: basePayload([{ en: 'nation', es: 'nación', audio_key: 'k' }]),
      },
    })

    await typeInto(wrapper, 'nación')
    const finish = wrapper.findAll('button').find((b) => b.text().includes('Finish'))!
    await finish.trigger('click')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    const result = events![0][0] as { correct: boolean; score: number }
    expect(result.correct).toBe(true)
    expect(result.score).toBe(100)
  })

  it('in guess mode, sends meta.vocab_delta proportional to cold guesses', async () => {
    const wrapper = mount(WordForge, {
      props: {
        payload: {
          ...basePayload([
            { en: 'nation', es: 'nación', audio_key: 'k' },
            { en: 'station', es: 'estación', audio_key: 'k2' },
          ]),
          is_guess_mode: true,
          vocab_counter_per_correct: 1,
          closer_message: 'You produced {count} Spanish words.',
        },
      },
    })

    await typeInto(wrapper, 'nación')
    const nextBtn = wrapper.findAll('button').find((b) => b.text().includes('Next word'))!
    await nextBtn.trigger('click')
    await flushPromises()

    await typeInto(wrapper, 'estación')
    const finish = wrapper.findAll('button').find((b) => b.text().includes('Finish'))!
    await finish.trigger('click')

    // Closer screen renders with substituted count.
    expect(wrapper.text()).toContain('You produced 2 Spanish words.')
    const cont = wrapper.findAll('button').find((b) => b.text().includes('Continue'))!
    await cont.trigger('click')

    const result = wrapper.emitted('complete')![0][0] as {
      meta?: Record<string, unknown>
    }
    expect(result.meta?.vocab_delta).toBe(2)
    expect(result.meta?.cold_guesses).toBe(2)
  })

  it('does not advance past a wrong character (the Next button only appears after forge)', async () => {
    const wrapper = mount(WordForge, {
      props: {
        payload: basePayload([{ en: 'nation', es: 'nación', audio_key: 'k' }]),
      },
    })

    await typeInto(wrapper, 'naz')
    expect(wrapper.findAll('button').find((b) => b.text().includes('Finish'))).toBeUndefined()
    expect(wrapper.findAll('button').find((b) => b.text().includes('Next word'))).toBeUndefined()
  })

  it('accepts accent-less input as correct and reveals the proper accented form', async () => {
    const wrapper = mount(WordForge, {
      props: {
        payload: basePayload([{ en: 'information', es: 'información', audio_key: 'k' }]),
      },
    })

    // Type the un-accented form — should NOT show the coach-error line at the o/ó slot.
    await typeInto(wrapper, 'informacion')
    expect(wrapper.text()).not.toMatch(/Almost — that letter does not match/i)
    // Forge should have fired — input becomes the accented form and a coach reveal renders.
    expect(wrapper.text()).toContain('Real spelling: información')
    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('información')
    // Next button is available since forge ran.
    expect(wrapper.findAll('button').find((b) => b.text().includes('Finish'))).toBeDefined()
  })

  it('per-letter validation treats accent-only differences as matching (no clay tint on o/ó)', async () => {
    const wrapper = mount(WordForge, {
      props: {
        payload: basePayload([{ en: 'information', es: 'información', audio_key: 'k' }]),
      },
    })

    // Type up to and including the would-be-accent index without forging.
    await typeInto(wrapper, 'informaci')
    const slots = wrapper.findAll('span.es.inline-flex')
    // index 8 is 'i' (matches), nothing wrong yet.
    expect(slots[8].classes().join(' ')).toMatch(/border-success/)
    // Now type the un-accented 'o' that should match 'ó' tolerantly.
    await typeInto(wrapper, 'informacio')
    const slotsAfter = wrapper.findAll('span.es.inline-flex')
    expect(slotsAfter[9].classes().join(' ')).toMatch(/border-success/)
    expect(slotsAfter[9].classes().join(' ')).not.toMatch(/border-coach/)
  })

  it('still rejects genuinely wrong letters even when the surrounding chars would be accent-tolerant', async () => {
    const wrapper = mount(WordForge, {
      props: {
        payload: basePayload([{ en: 'information', es: 'información', audio_key: 'k' }]),
      },
    })

    await typeInto(wrapper, 'informaq')
    expect(wrapper.text()).toMatch(/Almost — that letter does not match/i)
    const slots = wrapper.findAll('span.es.inline-flex')
    expect(slots[7].classes().join(' ')).toMatch(/border-coach/)
  })
})
