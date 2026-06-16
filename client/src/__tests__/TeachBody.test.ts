import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import TeachBody from '@/components/TeachBody.vue'
import EsAudioWord from '@/components/EsAudioWord.vue'
import { parseTeachMarkdown } from '@/lib/parseTeachMarkdown'
import { audioKey, type AudioApi } from '@/composables/audioInjection'

function audioStub(): { api: AudioApi; play: ReturnType<typeof vi.fn> } {
  const play = vi.fn().mockResolvedValue(undefined)
  return {
    api: { play, preload: vi.fn().mockResolvedValue(undefined), isSilent: ref(true) },
    play,
  }
}

describe('TeachBody', () => {
  it('renders a paragraph with mixed text + bold + es tokens', () => {
    const blocks = parseTeachMarkdown('A **bold** word and [[hola]].')
    const wrapper = mount(TeachBody, { props: { blocks } })
    expect(wrapper.find('p strong').text()).toBe('bold')
    expect(wrapper.findComponent(EsAudioWord).exists()).toBe(true)
    expect(wrapper.findComponent(EsAudioWord).props('es')).toBe('hola')
  })

  it('renders a bulleted list with one EsAudioWord per item', () => {
    const blocks = parseTeachMarkdown('- [[hola]] — hello\n- [[adiós]] — bye')
    const wrapper = mount(TeachBody, { props: { blocks } })
    const items = wrapper.findAll('li')
    expect(items).toHaveLength(2)
    const words = wrapper.findAllComponents(EsAudioWord)
    expect(words).toHaveLength(2)
    expect(words[0].props('es')).toBe('hola')
    expect(words[1].props('es')).toBe('adiós')
  })

  it('passes the audioKey + speakEs props through to EsAudioWord', () => {
    const blocks = parseTeachMarkdown('([[CA-sa|tts_word_casa|casa]])')
    const wrapper = mount(TeachBody, { props: { blocks } })
    const word = wrapper.findComponent(EsAudioWord)
    expect(word.props('audioKey')).toBe('tts_word_casa')
    expect(word.props('speakEs')).toBe('casa')
  })

  it('wires through to the injected audio provider when an EsAudioWord is clicked', async () => {
    const { api, play } = audioStub()
    const blocks = parseTeachMarkdown('Say [[buenos días|tts_chunk_buenos_dias]].')
    const wrapper = mount(TeachBody, {
      props: { blocks },
      global: { provide: { [audioKey as symbol]: api } },
    })
    await wrapper.findComponent(EsAudioWord).trigger('click')
    expect(play).toHaveBeenCalledWith('tts_chunk_buenos_dias', { es: 'buenos días' })
  })

  it('renders raw HTML chars as visible text, not injected DOM elements', () => {
    const blocks = parseTeachMarkdown('Use <div> & friends.')
    const wrapper = mount(TeachBody, { props: { blocks } })
    // Vue's {{ }} interpolation creates a text node, so the <div> stays as literal
    // characters — no element gets injected into the paragraph.
    const p = wrapper.find('p')
    expect(p.element.children.length).toBe(0)
    expect(p.text()).toBe('Use <div> & friends.')
  })
})
