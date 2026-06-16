import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import MultipleChoiceBase from '@/components/exercises/_bases/MultipleChoiceBase.vue'

const options = [
  { label: 'a', value: 'a' },
  { label: 'e', value: 'e' },
  { label: 'i', value: 'i' },
]

describe('MultipleChoiceBase', () => {
  it('emits complete({correct: true}) on a correct click', async () => {
    const wrapper = mount(MultipleChoiceBase, {
      props: { prompt: 'Tap the vowel', options, correctValue: 'e' },
    })

    const buttons = wrapper.findAll('button[type="button"]').filter((b) => b.text() === 'e')
    await buttons[0].trigger('click')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect(events![0][0]).toMatchObject({ correct: true })
  })

  it('coaches on the first wrong tap and emits complete:false on the second (legacy default)', async () => {
    const wrapper = mount(MultipleChoiceBase, {
      props: { prompt: 'Tap the vowel', options, correctValue: 'e' },
    })

    const aBtn = wrapper.findAll('button[type="button"]').find((b) => b.text() === 'a')!
    await aBtn.trigger('click')

    // First wrong: coaching message visible, no complete yet.
    expect(wrapper.emitted('complete')).toBeFalsy()
    expect(wrapper.text()).toMatch(/not quite|listen|try once more/i)

    // Second wrong (different option): now emits complete:false.
    const iBtn = wrapper.findAll('button[type="button"]').find((b) => b.text() === 'i')!
    await iBtn.trigger('click')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect(events![0][0]).toMatchObject({ correct: false })
  })

  describe('with require-perfect (mastery gate)', () => {
    it('never commits a wrong tap, no matter how many times the user tries wrong', async () => {
      const wrapper = mount(MultipleChoiceBase, {
        props: { prompt: 'Tap the vowel', options, correctValue: 'e', requirePerfect: true },
      })

      await wrapper
        .findAll('button[type="button"]')
        .find((b) => b.text() === 'a')!
        .trigger('click')
      await wrapper
        .findAll('button[type="button"]')
        .find((b) => b.text() === 'i')!
        .trigger('click')
      await wrapper
        .findAll('button[type="button"]')
        .find((b) => b.text() === 'a')!
        .trigger('click')

      expect(wrapper.emitted('complete')).toBeFalsy()

      await wrapper
        .findAll('button[type="button"]')
        .find((b) => b.text() === 'e')!
        .trigger('click')
      const events = wrapper.emitted('complete')
      expect(events).toBeTruthy()
      expect(events![0][0]).toMatchObject({ correct: true, score: 100 })
    })

    it('persists the coach tint on every wrong option the user has tapped', async () => {
      const wrapper = mount(MultipleChoiceBase, {
        props: { prompt: 'Tap the vowel', options, correctValue: 'e', requirePerfect: true },
      })

      await wrapper
        .findAll('button[type="button"]')
        .find((b) => b.text() === 'a')!
        .trigger('click')
      await wrapper
        .findAll('button[type="button"]')
        .find((b) => b.text() === 'i')!
        .trigger('click')

      const aBtn = wrapper.findAll('button[type="button"]').find((b) => b.text() === 'a')!
      const iBtn = wrapper.findAll('button[type="button"]').find((b) => b.text() === 'i')!
      expect(aBtn.classes().join(' ')).toMatch(/border-coach/)
      expect(iBtn.classes().join(' ')).toMatch(/border-coach/)
    })

    it('uses coachingPerValue override on a wrong tap when the value key matches', async () => {
      const wrapper = mount(MultipleChoiceBase, {
        props: {
          prompt: 'Tap the vowel',
          options,
          correctValue: 'e',
          requirePerfect: true,
          coaching: 'Generic coach default.',
          coachingPerValue: { a: 'Missing accent.', i: 'Wrong sound entirely.' },
        },
      })

      await wrapper
        .findAll('button[type="button"]')
        .find((b) => b.text() === 'a')!
        .trigger('click')
      expect(wrapper.text()).toContain('Missing accent.')
      expect(wrapper.text()).not.toContain('Generic coach default.')

      await wrapper
        .findAll('button[type="button"]')
        .find((b) => b.text() === 'i')!
        .trigger('click')
      expect(wrapper.text()).toContain('Wrong sound entirely.')
    })

    it('falls back to generic coach default when no per-value key matches', async () => {
      const wrapper = mount(MultipleChoiceBase, {
        props: {
          prompt: 'Tap the vowel',
          options,
          correctValue: 'e',
          requirePerfect: true,
          coachingPerValue: { a: 'Missing accent.' },
        },
      })
      // 'i' is not in coachingPerValue → falls through to the generic default.
      await wrapper
        .findAll('button[type="button"]')
        .find((b) => b.text() === 'i')!
        .trigger('click')
      expect(wrapper.text()).toMatch(/Not quite/i)
    })
  })
})
