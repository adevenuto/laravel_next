import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import TapTargetBase from '@/components/exercises/_bases/TapTargetBase.vue'

const targets = [
  { id: 'h', label: 'h', isAnswer: true }, // silent
  { id: 'o', label: 'o', isAnswer: false },
  { id: 'l', label: 'l', isAnswer: false },
  { id: 'a', label: 'a', isAnswer: false },
]

describe('TapTargetBase — multi-select', () => {
  it('Next is disabled with no selection', () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the silent letters', targets, multiple: true },
    })
    const nextBtn = wrapper.findAll('button').find((b) => b.text() === 'Next')!
    expect(nextBtn.attributes('disabled')).toBeDefined()
  })

  it('Next stays disabled while any wrong target is included', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the silent letters', targets, multiple: true },
    })
    const buttons = wrapper.findAll('button[type="button"]')
    await buttons.find((b) => b.text() === 'h')!.trigger('click')
    await buttons.find((b) => b.text() === 'o')!.trigger('click')
    const nextBtn = wrapper.findAll('button').find((b) => b.text() === 'Next')!
    expect(nextBtn.attributes('disabled')).toBeDefined()
  })

  it('Next enables only once selection matches the answer set exactly', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the silent letters', targets, multiple: true },
    })
    const buttons = wrapper.findAll('button[type="button"]')

    await buttons.find((b) => b.text() === 'h')!.trigger('click')
    await buttons.find((b) => b.text() === 'o')!.trigger('click')
    let nextBtn = wrapper.findAll('button').find((b) => b.text() === 'Next')!
    expect(nextBtn.attributes('disabled')).toBeDefined()

    // Deselect the wrong tile — now selection is exactly {h} which matches isAnswer set.
    await buttons.find((b) => b.text() === 'o')!.trigger('click')
    nextBtn = wrapper.findAll('button').find((b) => b.text() === 'Next')!
    expect(nextBtn.attributes('disabled')).toBeUndefined()

    await nextBtn.trigger('click')
    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean; score: number }).correct).toBe(true)
    expect((events![0][0] as { correct: boolean; score: number }).score).toBe(100)
  })

  it('shows the coach message on the first wrong tap and only once', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the silent letters', targets, multiple: true },
    })

    const buttons = wrapper.findAll('button[type="button"]')
    await buttons.find((b) => b.text() === 'o')!.trigger('click')
    expect(wrapper.text()).toMatch(/Not that one/i)

    // Deselect and tap another wrong letter — coach line stays but isn't duplicated.
    await buttons.find((b) => b.text() === 'o')!.trigger('click')
    await buttons.find((b) => b.text() === 'l')!.trigger('click')
    const coachMatches = wrapper.text().match(/Not that one/g) ?? []
    expect(coachMatches.length).toBe(1)
  })

  it('colors a correct tap with the success border before Next is pressed', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the silent letters', targets, multiple: true },
    })
    const hButton = wrapper.findAll('button[type="button"]').find((b) => b.text() === 'h')!
    await hButton.trigger('click')
    expect(hButton.classes().join(' ')).toMatch(/border-success/)
  })

  it('colors a wrong tap with the coach border before Next is pressed', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the silent letters', targets, multiple: true },
    })
    const oButton = wrapper.findAll('button[type="button"]').find((b) => b.text() === 'o')!
    await oButton.trigger('click')
    expect(oButton.classes().join(' ')).toMatch(/border-coach/)
  })
})

describe('TapTargetBase — single-select', () => {
  const single = [
    { id: '0', label: 'ca', isAnswer: false },
    { id: '1', label: 'sa', isAnswer: true },
  ]

  it('auto-commits on the first correct tap (no Check button)', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the stressed syllable', targets: single, multiple: false },
    })

    await wrapper
      .findAll('button[type="button"]')
      .find((b) => b.text() === 'sa')!
      .trigger('click')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean }).correct).toBe(true)
    expect(
      wrapper.findAll('button[type="button"]').find((b) => b.text() === 'Check')
    ).toBeUndefined()
  })

  it('never commits a wrong tap (mastery gate): user must keep tapping until they hit the right tile', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the stressed syllable', targets: single, multiple: false },
    })

    const caButton = wrapper.findAll('button[type="button"]').find((b) => b.text() === 'ca')!
    await caButton.trigger('click')
    expect(wrapper.emitted('complete')).toBeUndefined()
    expect(wrapper.text()).toMatch(/listen again/i)

    // Re-tapping the same wrong tile (or any wrong tile) still does not commit.
    await caButton.trigger('click')
    expect(wrapper.emitted('complete')).toBeUndefined()

    // Only correct tap commits.
    await wrapper
      .findAll('button[type="button"]')
      .find((b) => b.text() === 'sa')!
      .trigger('click')
    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean }).correct).toBe(true)
  })

  it('persists the wrong-on tint on every tile the user has tried', async () => {
    const triple = [
      { id: '0', label: 'ca', isAnswer: false },
      { id: '1', label: 'sa', isAnswer: true },
      { id: '2', label: 'ta', isAnswer: false },
    ]
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the stressed syllable', targets: triple, multiple: false },
    })

    await wrapper
      .findAll('button[type="button"]')
      .find((b) => b.text() === 'ca')!
      .trigger('click')
    await wrapper
      .findAll('button[type="button"]')
      .find((b) => b.text() === 'ta')!
      .trigger('click')

    const ca = wrapper.findAll('button[type="button"]').find((b) => b.text() === 'ca')!
    const ta = wrapper.findAll('button[type="button"]').find((b) => b.text() === 'ta')!
    expect(ca.classes().join(' ')).toMatch(/border-coach/)
    expect(ta.classes().join(' ')).toMatch(/border-coach/)
  })

  it('allows recovery: wrong then correct still emits correct: true', async () => {
    const wrapper = mount(TapTargetBase, {
      props: { prompt: 'Tap the stressed syllable', targets: single, multiple: false },
    })

    await wrapper
      .findAll('button[type="button"]')
      .find((b) => b.text() === 'ca')!
      .trigger('click')
    await wrapper
      .findAll('button[type="button"]')
      .find((b) => b.text() === 'sa')!
      .trigger('click')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean }).correct).toBe(true)
  })

  it('uses the custom coaching prop when provided', async () => {
    const wrapper = mount(TapTargetBase, {
      props: {
        prompt: 'Tap the stressed syllable',
        targets: single,
        multiple: false,
        coaching: 'Listen for the loudest beat.',
      },
    })

    await wrapper
      .findAll('button[type="button"]')
      .find((b) => b.text() === 'ca')!
      .trigger('click')
    expect(wrapper.text()).toContain('Listen for the loudest beat.')
  })
})
