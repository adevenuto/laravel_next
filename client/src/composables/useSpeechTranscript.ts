import { computed, ref, type ComputedRef, type Ref } from 'vue'

/**
 * Web Speech API SpeechRecognition wrapper, feature-detected and silent-failing.
 *
 * Used by ShadowRecord to grab a Spanish transcript of the user's spoken phrase
 * and compare it (via useFuzzyMatch) against the reference. Phase 5's boss
 * voice mode will use the same composable.
 *
 * Browser support is uneven — Chrome/Edge/Safari yes, Firefox no. When unsupported,
 * `isSupported` returns false and `start()` is a no-op. Callers should gate their
 * comparison UI on `isSupported.value`.
 */

export interface SpeechTranscriptApi {
  isSupported: ComputedRef<boolean>
  start: (lang?: string) => void
  stop: () => void
  transcript: Ref<string>
  alternatives: Ref<string[]>
  error: Ref<string | null>
  isListening: Ref<boolean>
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type AnyRecognition = any

function getRecognitionCtor(): AnyRecognition | null {
  if (typeof window === 'undefined') return null
  const w = window as unknown as {
    SpeechRecognition?: AnyRecognition
    webkitSpeechRecognition?: AnyRecognition
  }
  return w.SpeechRecognition ?? w.webkitSpeechRecognition ?? null
}

export function useSpeechTranscript(): SpeechTranscriptApi {
  const ctor = getRecognitionCtor()
  const isSupported = computed(() => ctor !== null)

  const transcript = ref('')
  const alternatives = ref<string[]>([])
  const error = ref<string | null>(null)
  const isListening = ref(false)

  let recognition: AnyRecognition | null = null

  function start(lang = 'es-MX') {
    if (!ctor) {
      if (import.meta.env.DEV) {
        // eslint-disable-next-line no-console
        console.debug('[useSpeechTranscript] not supported in this browser')
      }
      return
    }

    // Always recreate — recognition instances aren't reliable across restarts
    // in Safari.
    stop()
    transcript.value = ''
    alternatives.value = []
    error.value = null

    recognition = new ctor()
    recognition.lang = lang
    recognition.continuous = false
    recognition.interimResults = false
    recognition.maxAlternatives = 3

    recognition.onresult = (e: AnyRecognition) => {
      const first = e.results?.[0]
      if (!first) return
      // Pull all alternatives so the caller can rank them.
      const alts: string[] = []
      for (let i = 0; i < first.length; i++) {
        if (first[i]?.transcript) alts.push(first[i].transcript.trim())
      }
      alternatives.value = alts
      transcript.value = alts[0] ?? ''
    }

    recognition.onerror = (e: AnyRecognition) => {
      // 'no-speech' just means silence — not a fatal error from the user's POV.
      const code = e?.error ?? 'unknown'
      if (code !== 'no-speech' && code !== 'aborted') {
        error.value = code
        if (import.meta.env.DEV) {
          // eslint-disable-next-line no-console
          console.debug('[useSpeechTranscript] error', code)
        }
      }
    }

    recognition.onend = () => {
      isListening.value = false
    }

    try {
      recognition.start()
      isListening.value = true
    } catch (err) {
      // Some browsers throw if start() is called too soon after a prior stop.
      if (import.meta.env.DEV) {
        // eslint-disable-next-line no-console
        console.debug('[useSpeechTranscript] start threw', err)
      }
      isListening.value = false
    }
  }

  function stop() {
    if (!recognition) return
    try {
      recognition.stop()
    } catch {
      /* already stopped */
    }
    recognition = null
    isListening.value = false
  }

  return { isSupported, start, stop, transcript, alternatives, error, isListening }
}
