import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import TypedAnswerBase from '@/components/exercises/_bases/TypedAnswerBase.vue'

describe('TypedAnswerBase', () => {
  it('accepts an accent-less answer to an accented expected with coaching', async () => {
    const wrapper = mount(TypedAnswerBase, {
      props: { prompt: 'Type it', expected: ['información'] },
    })

    await wrapper.find('input').setValue('informacion')
    await wrapper.find('form').trigger('submit')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    const payload = events![0][0] as { correct: boolean; meta?: { coaching?: string } }
    expect(payload.correct).toBe(true)
    expect(payload.meta?.coaching).toMatch(/stress arrow|información/i)
  })

  it('asks for retry on a small typo (≥6 char Levenshtein-1)', async () => {
    const wrapper = mount(TypedAnswerBase, {
      props: { prompt: 'Type it', expected: ['información'] },
    })

    await wrapper.find('input').setValue('informasion')
    await wrapper.find('form').trigger('submit')

    // No emit yet — retry mode.
    expect(wrapper.emitted('complete')).toBeFalsy()
    expect(wrapper.text()).toMatch(/almost/i)
  })

  it('emits complete:false after the retry is also wrong', async () => {
    const wrapper = mount(TypedAnswerBase, {
      props: { prompt: 'Type it', expected: ['información'] },
    })

    // First wrong → retry.
    await wrapper.find('input').setValue('informasion')
    await wrapper.find('form').trigger('submit')

    // Second wrong → settled.
    await wrapper.find('input').setValue('totalmente-equivocado')
    await wrapper.find('form').trigger('submit')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean }).correct).toBe(false)
  })
})
