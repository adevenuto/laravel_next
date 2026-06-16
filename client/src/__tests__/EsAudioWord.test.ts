import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import EsAudioWord from '@/components/EsAudioWord.vue'
import { audioKey, type AudioApi } from '@/composables/audioInjection'

function makeAudio(): { api: AudioApi; play: ReturnType<typeof vi.fn> } {
  const play = vi.fn().mockResolvedValue(undefined)
  const api: AudioApi = {
    play,
    preload: vi.fn().mockResolvedValue(undefined),
    isSilent: ref(true),
  }
  return { api, play }
}

describe('EsAudioWord', () => {
  it('renders the Spanish text and an aria-label that includes it', () => {
    const wrapper = mount(EsAudioWord, { props: { es: 'buenos días' } })
    expect(wrapper.text()).toContain('buenos días')
    expect(wrapper.attributes('aria-label')).toBe('Hear buenos días')
  })

  it('calls audio.play with the explicit audioKey + es when clicked', async () => {
    const { api, play } = makeAudio()
    const wrapper = mount(EsAudioWord, {
      props: { es: 'buenos días', audioKey: 'tts_chunk_buenos_dias' },
      global: { provide: { [audioKey as symbol]: api } },
    })
    await wrapper.trigger('click')
    expect(play).toHaveBeenCalledTimes(1)
    expect(play).toHaveBeenCalledWith('tts_chunk_buenos_dias', { es: 'buenos días' })
  })

  it('falls back to a derived key when no audioKey prop is passed', async () => {
    const { api, play } = makeAudio()
    const wrapper = mount(EsAudioWord, {
      props: { es: 'hola' },
      global: { provide: { [audioKey as symbol]: api } },
    })
    await wrapper.trigger('click')
    expect(play).toHaveBeenCalledWith('tts_chunk_hola', { es: 'hola' })
  })

  it('strips accents + special chars when deriving the key', async () => {
    const { api, play } = makeAudio()
    const wrapper = mount(EsAudioWord, {
      props: { es: '¿Cómo estás?' },
      global: { provide: { [audioKey as symbol]: api } },
    })
    await wrapper.trigger('click')
    expect(play).toHaveBeenCalledWith('tts_chunk_como_estas', { es: '¿Cómo estás?' })
  })

  it('uses speakEs override for the spoken fallback when provided', async () => {
    const { api, play } = makeAudio()
    const wrapper = mount(EsAudioWord, {
      props: { es: 'CA-sa', audioKey: 'tts_word_casa', speakEs: 'casa' },
      global: { provide: { [audioKey as symbol]: api } },
    })
    await wrapper.trigger('click')
    expect(play).toHaveBeenCalledWith('tts_word_casa', { es: 'casa' })
  })

  it('does not throw when clicked with no audio provider injected', async () => {
    const wrapper = mount(EsAudioWord, { props: { es: 'hola' } })
    await expect(wrapper.trigger('click')).resolves.not.toThrow()
  })
})
