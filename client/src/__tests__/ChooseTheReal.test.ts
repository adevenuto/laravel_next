import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import ChooseTheReal from '@/components/exercises/ChooseTheReal.vue'
import type { ChooseTheRealPayload } from '@/types/exercises'

const payload: ChooseTheRealPayload = {
  prompt: 'One of these is real Spanish.',
  rounds: [
    {
      en: 'notification',
      options: ['notificacion', 'notificatión', 'notificación'],
      answer_index: 2,
      why_wrong: ['Missing accent.', 'Kept the English -t-. Swap it.', null],
    },
  ],
}

describe('ChooseTheReal', () => {
  it('shows the seeded why_wrong copy when a wrong option is tapped', async () => {
    const wrapper = mount(ChooseTheReal, { props: { payload } })

    const buttons = wrapper.findAll('button')
    await buttons.find((b) => b.text().includes('notificacion'))!.trigger('click')
    expect(wrapper.text()).toContain('Missing accent.')

    await buttons.find((b) => b.text().includes('notificatión'))!.trigger('click')
    expect(wrapper.text()).toContain('Kept the English -t-. Swap it.')
  })

  it('emits complete: true when the correct option is tapped on the only round', async () => {
    const wrapper = mount(ChooseTheReal, { props: { payload } })

    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('notificación'))!
      .trigger('click')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean; score: number }).correct).toBe(true)
    expect((events![0][0] as { correct: boolean; score: number }).score).toBe(100)
  })

  it('shows the English gloss alongside the prompt', () => {
    const wrapper = mount(ChooseTheReal, { props: { payload } })
    expect(wrapper.text()).toContain('notification')
  })
})
