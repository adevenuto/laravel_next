import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import ArticleAttach from '@/components/exercises/ArticleAttach.vue'
import type { ArticleAttachPayload } from '@/types/exercises'

const payload: ArticleAttachPayload = {
  prompt: 'Attach the right article.',
  articles: ['la', 'el'],
  nouns: [
    { es: 'información', article: 'la' },
    { es: 'doctor', article: 'el' },
    { es: 'celebración', article: 'la' },
  ],
}

describe('ArticleAttach', () => {
  it('Next is disabled until every noun is correctly assigned', async () => {
    const wrapper = mount(ArticleAttach, { props: { payload } })
    const next = wrapper.findAll('button').find((b) => b.text() === 'Next')!
    expect(next.attributes('disabled')).toBeDefined()
  })

  it('coaches when a user taps a noun without arming an article first', async () => {
    const wrapper = mount(ArticleAttach, { props: { payload } })
    const nounBtn = wrapper.findAll('button').find((b) => b.text().includes('información'))!
    await nounBtn.trigger('click')
    expect(wrapper.text()).toContain('Pick an article first')
  })

  it('colors a correctly-assigned noun with success and a wrong one with coach', async () => {
    const wrapper = mount(ArticleAttach, { props: { payload } })

    // Arm la, tap información (correct).
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'la')!
      .trigger('click')
    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('información'))!
      .trigger('click')

    const infoBtn = wrapper.findAll('button').find((b) => b.text().includes('información'))!
    expect(infoBtn.classes().join(' ')).toMatch(/border-success/)

    // Arm la, tap doctor (wrong — doctor takes el).
    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('doctor'))!
      .trigger('click')
    const docBtn = wrapper.findAll('button').find((b) => b.text().includes('doctor'))!
    expect(docBtn.classes().join(' ')).toMatch(/border-coach/)
  })

  it('emits complete: true / score: 100 when every noun has its correct article', async () => {
    const wrapper = mount(ArticleAttach, { props: { payload } })

    // Arm la → tap the two -ción nouns.
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'la')!
      .trigger('click')
    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('información'))!
      .trigger('click')
    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('celebración'))!
      .trigger('click')

    // Arm el → tap doctor.
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'el')!
      .trigger('click')
    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('doctor'))!
      .trigger('click')

    const next = wrapper.findAll('button').find((b) => b.text() === 'Next')!
    expect(next.attributes('disabled')).toBeUndefined()
    await next.trigger('click')

    const events = wrapper.emitted('complete')
    expect(events).toBeTruthy()
    expect((events![0][0] as { correct: boolean; score: number }).correct).toBe(true)
    expect((events![0][0] as { correct: boolean; score: number }).score).toBe(100)
  })

  it('shows the gentle reminder when any noun is currently misassigned', async () => {
    const wrapper = mount(ArticleAttach, { props: { payload } })
    await wrapper
      .findAll('button')
      .find((b) => b.text() === 'el')!
      .trigger('click')
    await wrapper
      .findAll('button')
      .find((b) => b.text().includes('información'))!
      .trigger('click')
    expect(wrapper.text()).toMatch(/every .* noun is feminine/i)
  })
})
