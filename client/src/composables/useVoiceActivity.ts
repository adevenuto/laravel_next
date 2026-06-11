import { ref, type Ref } from 'vue'

/**
 * Lightweight Voice Activity Detection for the ShadowRecord exercise.
 *
 * Reads RMS amplitude from a Web Audio AnalyserNode each frame. To survive
 * real-world conditions (room tone, fan noise, breath, plosives) the detector
 * uses TWO thresholds with hysteresis:
 *
 *   - `loudThreshold` (e.g. 0.05) — RMS must clear this to enter "speaking"
 *   - `quietThreshold` (e.g. 0.018) — RMS must fall below this to enter "fading"
 *
 * Once enough silence has accumulated in "fading" (≥ `silenceMs`), AND we've
 * already collected at least `minSpeechMs` of speech in this session, the
 * `onSilence` callback fires.
 *
 * RMS is also smoothed via an exponential moving average so isolated frame
 * spikes (a click, a "p", a chair creak) don't bounce us out of fading.
 *
 * In dev mode the loop emits a `console.debug` line every ~500ms showing the
 * smoothed RMS + state — invaluable for tuning thresholds against your mic.
 */

export interface VoiceActivityOptions {
  loudThreshold?: number
  quietThreshold?: number
  silenceMs?: number
  minSpeechMs?: number
  smoothing?: number
}

export interface VoiceActivityApi {
  start: (stream: MediaStream) => void
  stop: () => void
  onSilence: (cb: () => void) => void
  isSpeaking: Ref<boolean>
  teardown: () => void
}

const DEFAULT_LOUD_THRESHOLD = 0.05
const DEFAULT_QUIET_THRESHOLD = 0.015
const DEFAULT_SILENCE_MS = 1100
const DEFAULT_MIN_SPEECH_MS = 500
const DEFAULT_SMOOTHING = 0.25 // higher = more responsive (0..1)

type VadPhase = 'idle' | 'speaking' | 'fading' | 'fired'

export function useVoiceActivity(options: VoiceActivityOptions = {}): VoiceActivityApi {
  const loudThreshold = options.loudThreshold ?? DEFAULT_LOUD_THRESHOLD
  const quietThreshold = options.quietThreshold ?? DEFAULT_QUIET_THRESHOLD
  const silenceMs = options.silenceMs ?? DEFAULT_SILENCE_MS
  const minSpeechMs = options.minSpeechMs ?? DEFAULT_MIN_SPEECH_MS
  const smoothing = options.smoothing ?? DEFAULT_SMOOTHING

  const isSpeaking = ref(false)
  let silenceCb: (() => void) | null = null

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let audioCtx: any | null = null
  let analyser: AnalyserNode | null = null
  let source: MediaStreamAudioSourceNode | null = null
  let dataArray: Uint8Array | null = null
  let activeStream: MediaStream | null = null
  let rafId: number | null = null

  // Per-session state.
  let phase: VadPhase = 'idle'
  let speechTotalMs = 0
  let silenceStartedAt: number | null = null
  let lastFrameTime = 0
  let smoothedRms = 0
  let lastDebugAt = 0

  function rawRms(): number {
    if (!analyser || !dataArray) return 0
    analyser.getByteTimeDomainData(dataArray)
    let sum = 0
    for (let i = 0; i < dataArray.length; i++) {
      const normalized = (dataArray[i] - 128) / 128
      sum += normalized * normalized
    }
    return Math.sqrt(sum / dataArray.length)
  }

  function tick() {
    if (!analyser) return

    const now = performance.now()
    const dt = lastFrameTime === 0 ? 0 : now - lastFrameTime
    lastFrameTime = now

    const raw = rawRms()
    smoothedRms = smoothing * raw + (1 - smoothing) * smoothedRms
    const level = smoothedRms

    switch (phase) {
      case 'idle':
        if (level >= loudThreshold) {
          phase = 'speaking'
          isSpeaking.value = true
          silenceStartedAt = null
        }
        break

      case 'speaking':
        speechTotalMs += dt
        if (level < quietThreshold) {
          phase = 'fading'
          isSpeaking.value = false
          silenceStartedAt = now
        }
        break

      case 'fading':
        // Stay in fading even if level pops up slightly — only a *loud* spike
        // moves us back to speaking. Brief blips during the pause shouldn't
        // reset the silence timer.
        if (level >= loudThreshold) {
          phase = 'speaking'
          isSpeaking.value = true
          silenceStartedAt = null
        } else {
          const silenceFor = silenceStartedAt === null ? 0 : now - silenceStartedAt
          if (speechTotalMs >= minSpeechMs && silenceFor >= silenceMs && silenceCb) {
            phase = 'fired'
            silenceCb()
            return
          }
        }
        break

      case 'fired':
        // No more work; loop self-terminates by not rescheduling.
        return
    }

    if (import.meta.env.DEV) {
      if (now - lastDebugAt > 500) {
        lastDebugAt = now
        // eslint-disable-next-line no-console
        console.debug(
          `[VAD] phase=${phase} rms=${level.toFixed(3)} speech=${speechTotalMs.toFixed(0)}ms ` +
            `silence=${silenceStartedAt === null ? '-' : (now - silenceStartedAt).toFixed(0) + 'ms'}`
        )
      }
    }

    rafId = requestAnimationFrame(tick)
  }

  function start(stream: MediaStream) {
    if (rafId !== null) {
      cancelAnimationFrame(rafId)
      rafId = null
    }

    if (!audioCtx) {
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const AudioCtor: any =
        (window as unknown as { AudioContext?: unknown }).AudioContext ??
        (window as unknown as { webkitAudioContext?: unknown }).webkitAudioContext

      if (!AudioCtor) return

      audioCtx = new AudioCtor()
    }

    if (audioCtx && audioCtx.state === 'suspended' && typeof audioCtx.resume === 'function') {
      void audioCtx.resume()
    }

    // Same-stream sessions reuse the existing source+analyser chain — Chrome
    // silently produces empty data if you attach the same MediaStream to
    // multiple MediaStreamAudioSourceNodes in one context.
    if (!source || !analyser || activeStream !== stream) {
      if (source && analyser) {
        try {
          source.disconnect(analyser)
        } catch {
          /* already disconnected */
        }
      }
      source = audioCtx.createMediaStreamSource(stream)
      analyser = audioCtx.createAnalyser() as AnalyserNode
      analyser.fftSize = 512
      analyser.smoothingTimeConstant = 0.85
      dataArray = new Uint8Array(analyser.fftSize)
      source!.connect(analyser)
      activeStream = stream
    }

    phase = 'idle'
    speechTotalMs = 0
    silenceStartedAt = null
    lastFrameTime = 0
    smoothedRms = 0
    lastDebugAt = 0
    isSpeaking.value = false
    rafId = requestAnimationFrame(tick)
  }

  function stop() {
    if (rafId !== null) {
      cancelAnimationFrame(rafId)
      rafId = null
    }
    isSpeaking.value = false
  }

  function teardown() {
    silenceCb = null
    stop()
    if (source && analyser) {
      try {
        source.disconnect(analyser)
      } catch {
        /* already disconnected */
      }
    }
    source = null
    analyser = null
    dataArray = null
    activeStream = null
    if (audioCtx && typeof audioCtx.close === 'function') {
      void audioCtx.close()
    }
    audioCtx = null
  }

  function onSilence(cb: () => void) {
    silenceCb = cb
  }

  return { start, stop, onSilence, isSpeaking, teardown }
}
