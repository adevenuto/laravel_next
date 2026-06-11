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

  it('coaches on the first wrong tap and emits complete:false on the second', async () => {
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
})
