import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import TapTargetBase from '@/components/exercises/_bases/TapTargetBase.vue'

const targets = [
  { id: 'h', label: 'h', isAnswer: true }, // silent
  { id: 'o', label: 'o', isAnswer: false },
  { id: 'l', label: 'l', isAnswer: false },
  { id: 'a', label: 'a', isAnswer: false },
]

describe('TapTargetBase', () => {
  it('emits correct: true when all answer targets are tapped and no others', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the silent letters', targets, multiple: true },
    })

    const buttons = wrapper.findAll('button[type="button"]')
    const hButton = buttons.find((b) => b.text() === 'h')!
    await hButton.trigger('click')

    const checkBtn = buttons.find((b) => b.text() === 'Check')!
    await checkBtn.trigger('click')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean }).correct).toBe(true)
  })

  it('emits correct: false when a wrong target is included', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the silent letters', targets, multiple: true },
    })

    const buttons = wrapper.findAll('button[type="button"]')
    await buttons.find((b) => b.text() === 'h')!.trigger('click')
    await buttons.find((b) => b.text() === 'o')!.trigger('click')
    await buttons.find((b) => b.text() === 'Check')!.trigger('click')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean }).correct).toBe(false)
  })

  it('single-select mode allows only one tap', async () => {
    const single = [
      { id: '0', label: 'ca', isAnswer: false },
      { id: '1', label: 'sa', isAnswer: true },
    ]
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the stressed syllable', targets: single, multiple: false },
    })

    const buttons = wrapper.findAll('button[type="button"]')
    await buttons.find((b) => b.text() === 'sa')!.trigger('click')
    await buttons.find((b) => b.text() === 'Check')!.trigger('click')

    const events = wrapper.emitted('complete')
    expect((events![0][0] as { correct: boolean }).correct).toBe(true)
  })
})
