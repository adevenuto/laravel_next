import { levenshtein } from './useFuzzyMatch.levenshtein'

export interface FuzzyMatchResult {
  correct: boolean
  retry: boolean
  coaching: string | null
}

/**
 * Fuzzy-matcher for typed Spanish answers.
 *
 * Semantics (see DECISIONS.md, R5):
 *   1. Strip accents (NFD + combining-mark removal), lowercase, trim both sides.
 *      Exact equality → correct. Coaching only set when the expected form had
 *      accents the user did not type.
 *   2. Otherwise, if `expected` (stripped) is ≥6 chars AND Levenshtein ≤1,
 *      return `{correct: false, retry: true, coaching: "Almost — check spelling."}`.
 *   3. Otherwise, `{correct: false, retry: false, coaching: null}`.
 *
 * Transpositions (e.g., `infomración` vs `información`) are Levenshtein 2 by
 * classical definition and fall through to (3). Documented; revisit in Phase 3
 * if WordForge usability demands it.
 */
export function useFuzzyMatch(expected: string, given: string): FuzzyMatchResult {
  const strippedExpected = strip(expected)
  const strippedGiven = strip(given)

  if (strippedExpected === strippedGiven) {
    const accentsDropped = hasAccents(expected) && !hasAccents(given) && strippedGiven.length > 0
    return {
      correct: true,
      retry: false,
      coaching: accentsDropped ? `Don't forget the stress arrow: ${expected.trim()}` : null,
    }
  }

  if (
    strippedExpected.length >= 6 &&
    strippedGiven.length > 0 &&
    levenshtein(strippedExpected, strippedGiven) <= 1
  ) {
    return {
      correct: false,
      retry: true,
      coaching: 'Almost — check spelling.',
    }
  }

  return { correct: false, retry: false, coaching: null }
}

function strip(s: string): string {
  // U+0300–U+036F = combining marks (covers all Spanish diacritics).
  return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim()
}

function hasAccents(s: string): boolean {
  // True iff NFD-decomposition produced any combining marks.
  return /[̀-ͯ]/.test(s.normalize('NFD'))
}
