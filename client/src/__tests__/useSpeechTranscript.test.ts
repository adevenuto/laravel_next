import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useSpeechTranscript } from '@/composables/useSpeechTranscript'

describe('useSpeechTranscript', () => {
  afterEach(() => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    delete (window as any).SpeechRecognition
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    delete (window as any).webkitSpeechRecognition
    vi.restoreAllMocks()
  })

  describe('when SpeechRecognition is not supported (e.g. Firefox)', () => {
    beforeEach(() => {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      delete (window as any).SpeechRecognition
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      delete (window as any).webkitSpeechRecognition
    })

    it('reports isSupported = false', () => {
      const sp = useSpeechTranscript()
      expect(sp.isSupported.value).toBe(false)
    })

    it('start() is a no-op (no throw, transcript stays empty)', () => {
      const sp = useSpeechTranscript()
      expect(() => sp.start('es-MX')).not.toThrow()
      expect(sp.transcript.value).toBe('')
      expect(sp.alternatives.value).toEqual([])
      expect(sp.isListening.value).toBe(false)
    })
  })

  describe('when SpeechRecognition IS supported', () => {
    interface FakeRecognition {
      lang: string
      continuous: boolean
      interimResults: boolean
      maxAlternatives: number
      onresult: ((e: unknown) => void) | null
      onerror: ((e: unknown) => void) | null
      onend: (() => void) | null
      start: () => void
      stop: () => void
    }

    let lastInstance: FakeRecognition | null = null

    beforeEach(() => {
      lastInstance = null
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      ;(window as any).SpeechRecognition = function () {
        const inst: FakeRecognition = {
          lang: '',
          continuous: false,
          interimResults: false,
          maxAlternatives: 0,
          onresult: null,
          onerror: null,
          onend: null,
          start: vi.fn(),
          stop: vi.fn(),
        }
        lastInstance = inst
        return inst
      }
    })

    it('reports isSupported = true and start() configures es-MX by default', () => {
      const sp = useSpeechTranscript()
      expect(sp.isSupported.value).toBe(true)

      sp.start()
      expect(lastInstance).not.toBeNull()
      expect(lastInstance!.lang).toBe('es-MX')
      expect(lastInstance!.continuous).toBe(false)
      expect(lastInstance!.maxAlternatives).toBe(3)
    })

    it('captures all alternatives on a result event and keeps the first as transcript', () => {
      const sp = useSpeechTranscript()
      sp.start('es-MX')

      const fakeEvent = {
        results: [
          {
            length: 3,
            0: { transcript: 'la mesa' },
            1: { transcript: 'la mesa.' },
            2: { transcript: 'la pesa' },
          },
        ],
      }
      lastInstance!.onresult!(fakeEvent)

      expect(sp.alternatives.value).toEqual(['la mesa', 'la mesa.', 'la pesa'])
      expect(sp.transcript.value).toBe('la mesa')
    })

    it("does not record an error for benign 'no-speech' events", () => {
      const sp = useSpeechTranscript()
      sp.start('es-MX')
      lastInstance!.onerror!({ error: 'no-speech' })
      expect(sp.error.value).toBeNull()
    })

    it('records an error for hard failures like network', () => {
      const sp = useSpeechTranscript()
      sp.start('es-MX')
      lastInstance!.onerror!({ error: 'network' })
      expect(sp.error.value).toBe('network')
    })
  })
})
