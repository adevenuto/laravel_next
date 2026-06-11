import { ref, type Ref } from 'vue'
import type { PlayFallback } from './audioInjection'

interface AudioSprite {
  src: string[]
  sprite: Record<string, [number, number]>
}

interface AudioApi {
  play: (key: string, fallback?: PlayFallback) => Promise<void>
  preload: () => Promise<void>
  isReady: Ref<boolean>
  isSilent: Ref<boolean>
}

/**
 * Per-lesson audio playback.
 *
 * Strategy:
 *   1. Look for an audio sprite at `/audio/sprites/<lessonSlug>.json`. Phase 6's
 *      `audio:generate` artisan command will produce these. When the sprite
 *      exists, play it via Howler — that's the real curriculum audio.
 *   2. When no sprite is found (Phase 2 default — no MP3s shipped yet) AND the
 *      caller passes a `fallback.es` Spanish string AND the browser exposes
 *      `window.speechSynthesis`, speak the Spanish text via the Web Speech API
 *      with an `es-MX` voice. This is a *development stopgap*, not curriculum:
 *      voice quality varies by OS / browser. Phase 6 sprites take precedence
 *      automatically once they ship.
 *   3. When neither is available, `play()` resolves to a silent no-op + dev log.
 *      The exercise still functions per §2: "the frontend must gracefully no-op
 *      if an audio key is missing."
 */
export function useAudio(lessonSlug: string): AudioApi {
  const isReady = ref(false)
  const isSilent = ref(false)

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let howl: any | null = null

  let cachedVoice: SpeechSynthesisVoice | null = null
  const speechSupported = typeof window !== 'undefined' && 'speechSynthesis' in window

  function pickSpanishVoice(lang: string): SpeechSynthesisVoice | null {
    if (!speechSupported) return null
    if (cachedVoice && cachedVoice.lang.toLowerCase().startsWith('es')) {
      return cachedVoice
    }
    const voices = window.speechSynthesis.getVoices()
    if (voices.length === 0) return null

    const lc = lang.toLowerCase()
    cachedVoice =
      voices.find((v) => v.lang.toLowerCase() === lc) ??
      voices.find((v) => v.lang.toLowerCase().startsWith('es-')) ??
      voices.find((v) => v.lang.toLowerCase().startsWith('es')) ??
      null
    return cachedVoice
  }

  function speak(es: string, lang: string) {
    if (!speechSupported || !es) return
    try {
      window.speechSynthesis.cancel()
      const utt = new SpeechSynthesisUtterance(es)
      utt.lang = lang
      utt.rate = 0.95
      utt.pitch = 1.0
      const voice = pickSpanishVoice(lang)
      if (voice) utt.voice = voice
      window.speechSynthesis.speak(utt)
    } catch (err) {
      if (import.meta.env.DEV) {
        // eslint-disable-next-line no-console
        console.debug('[useAudio] speech synth failed', err)
      }
    }
  }

  async function preload(): Promise<void> {
    if (isReady.value || isSilent.value) return

    try {
      const res = await fetch(`/audio/sprites/${lessonSlug}.json`)
      if (!res.ok) throw new Error(`sprite ${res.status}`)
      const sprite = (await res.json()) as AudioSprite

      const { Howl } = await import('howler')
      howl = new Howl({ src: sprite.src, sprite: sprite.sprite, preload: true })
      isReady.value = true
    } catch (err) {
      isSilent.value = true
      isReady.value = true

      // Pre-warm the voice list. Browsers populate voices asynchronously; the
      // one-shot handler ensures the cache is filled by the time someone taps
      // "Hear it" the first time.
      if (speechSupported && window.speechSynthesis.getVoices().length === 0) {
        window.speechSynthesis.onvoiceschanged = () => {
          cachedVoice = null
          pickSpanishVoice('es-MX')
        }
      } else {
        pickSpanishVoice('es-MX')
      }

      if (import.meta.env.DEV) {
        // eslint-disable-next-line no-console
        console.debug(
          `[useAudio] No sprite for "${lessonSlug}" — falling back to SpeechSynthesis.`,
          err
        )
      }
    }
  }

  async function play(key: string, fallback?: PlayFallback): Promise<void> {
    if (!isReady.value) await preload()

    // Real sprite available → curriculum audio wins.
    if (!isSilent.value && howl) {
      howl.play(key)
      return
    }

    // No sprite but caller provided Spanish text → SpeechSynthesis fallback.
    if (isSilent.value && fallback?.es && speechSupported) {
      speak(fallback.es, fallback.lang ?? 'es-MX')
      return
    }

    if (import.meta.env.DEV) {
      // eslint-disable-next-line no-console
      console.debug(`[useAudio] silent play("${key}") — no sprite, no fallback text`)
    }
  }

  return { play, preload, isReady, isSilent }
}
