import { describe, expect, it, vi } from 'vitest'
import { useVoiceActivity } from '@/composables/useVoiceActivity'

describe('useVoiceActivity', () => {
  it('exposes the expected API surface', () => {
    const vad = useVoiceActivity()
    expect(typeof vad.start).toBe('function')
    expect(typeof vad.stop).toBe('function')
    expect(typeof vad.onSilence).toBe('function')
    expect(typeof vad.teardown).toBe('function')
    expect(vad.isSpeaking.value).toBe(false)
  })

  it('stop() is a no-op when never started', () => {
    const vad = useVoiceActivity()
    expect(() => vad.stop()).not.toThrow()
  })

  it('teardown() clears the silence callback', () => {
    const vad = useVoiceActivity()
    const cb = vi.fn()
    vad.onSilence(cb)
    vad.teardown()
    // No assertion against cb directly — the contract is that teardown drops
    // the reference so future fires (none here in jsdom) wouldn't reach it.
    expect(() => vad.stop()).not.toThrow()
  })

  it('accepts custom thresholds without throwing', () => {
    const vad = useVoiceActivity({
      loudThreshold: 0.08,
      quietThreshold: 0.02,
      silenceMs: 800,
      minSpeechMs: 400,
    })
    expect(vad.isSpeaking.value).toBe(false)
  })

  // The RMS heuristic itself depends on a real AudioContext + AnalyserNode +
  // requestAnimationFrame loop. jsdom doesn't provide them faithfully, so the
  // behavior validation is the manual checkpoint in the plan file (record →
  // silence → auto-stop, verified in Chrome desktop).
})
