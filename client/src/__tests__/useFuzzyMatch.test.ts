import { describe, expect, it } from 'vitest'
import { useFuzzyMatch } from '@/composables/useFuzzyMatch'

describe('useFuzzyMatch', () => {
  it('returns correct + no coaching on an exact unaccented match', () => {
    const r = useFuzzyMatch('hola', 'hola')
    expect(r.correct).toBe(true)
    expect(r.retry).toBe(false)
    expect(r.coaching).toBeNull()
  })

  it('accepts an accent-less answer to an accented source but coaches gently', () => {
    const r = useFuzzyMatch('información', 'informacion')
    expect(r.correct).toBe(true)
    expect(r.retry).toBe(false)
    expect(r.coaching).toMatch(/stress arrow/i)
    expect(r.coaching).toContain('información')
  })

  it('does NOT coach when both source and answer are unaccented', () => {
    const r = useFuzzyMatch('hola', 'hola')
    expect(r.coaching).toBeNull()
  })

  it('treats a one-letter typo on an 8+ char word as retry', () => {
    const r = useFuzzyMatch('información', 'informasion')
    expect(r.correct).toBe(false)
    expect(r.retry).toBe(true)
    expect(r.coaching).toMatch(/almost/i)
  })

  it('does NOT offer retry on a one-letter typo for short words', () => {
    const r = useFuzzyMatch('hola', 'holo')
    expect(r.correct).toBe(false)
    expect(r.retry).toBe(false)
  })

  it('rejects empty input as not-correct, not-retry', () => {
    const r = useFuzzyMatch('información', '')
    expect(r.correct).toBe(false)
    expect(r.retry).toBe(false)
  })

  it('is case-insensitive', () => {
    const r = useFuzzyMatch('Información', 'INFORMACION')
    expect(r.correct).toBe(true)
  })

  it('trims leading and trailing whitespace', () => {
    const r = useFuzzyMatch('información', '  información   ')
    expect(r.correct).toBe(true)
    expect(r.coaching).toBeNull()
  })

  it('falls through transpositions (Levenshtein 2) to not-correct, not-retry', () => {
    // Transposed two chars: 'infomración' instead of 'información'.
    const r = useFuzzyMatch('información', 'infomración')
    expect(r.correct).toBe(false)
    expect(r.retry).toBe(false)
  })

  it('accepts an accented answer to an unaccented expected (no false coach)', () => {
    const r = useFuzzyMatch('casa', 'cása')
    expect(r.correct).toBe(true)
    expect(r.coaching).toBeNull()
  })

  it('preserves ñ correctly through NFD stripping', () => {
    // ñ ≠ n once decomposed: the tilde is a combining mark that we strip, so 'niño' → 'nino'.
    // 'nino' vs 'niño' → after stripping both equal 'nino'. Correct + coach because source had a mark.
    const r = useFuzzyMatch('niño', 'nino')
    expect(r.correct).toBe(true)
    expect(r.coaching).toMatch(/stress arrow/i)
    // Exact match (both ñ): no coach.
    const r2 = useFuzzyMatch('niño', 'niño')
    expect(r2.correct).toBe(true)
    expect(r2.coaching).toBeNull()
  })

  it('handles multi-word expected strings as a single comparison', () => {
    const r = useFuzzyMatch('más despacio', 'mas despacio')
    expect(r.correct).toBe(true)
    expect(r.coaching).toMatch(/stress arrow/i)
  })

  it('rejects a completely wrong word', () => {
    const r = useFuzzyMatch('información', 'gracias')
    expect(r.correct).toBe(false)
    expect(r.retry).toBe(false)
  })

  it('ignores punctuation differences from SpeechRecognition output (Hola, Hermano)', () => {
    // The browser SR returned "Hola Hermano" against seeded "Hola, Hermano".
    const r = useFuzzyMatch('Hola, Hermano', 'Hola Hermano')
    expect(r.correct).toBe(true)
    expect(r.coaching).toBeNull()
  })

  it('ignores Spanish opening punctuation (¿ ¡) and trailing ? !', () => {
    const r = useFuzzyMatch('¿Cómo estás?', 'Cómo estás')
    expect(r.correct).toBe(true)
    expect(r.coaching).toBeNull()
  })

  it('still coaches accent loss when punctuation also differs', () => {
    const r = useFuzzyMatch('¿Cómo estás?', 'como estas')
    expect(r.correct).toBe(true)
    expect(r.coaching).toMatch(/stress arrow/i)
    expect(r.coaching).toContain('¿Cómo estás?')
  })

  it('ignores a trailing period', () => {
    const r = useFuzzyMatch('estoy bien', 'estoy bien.')
    expect(r.correct).toBe(true)
    expect(r.coaching).toBeNull()
  })

  it('collapses runs of internal whitespace', () => {
    const r = useFuzzyMatch('hola hermano', 'hola   hermano')
    expect(r.correct).toBe(true)
    expect(r.coaching).toBeNull()
  })
})
