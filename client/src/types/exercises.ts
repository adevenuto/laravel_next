/* Payload shapes for the 8 Unit 1.1 exercise components.
   Mirror the seeder JSON in backend/database/seeders/ExerciseSeeder.php. */

export interface EarTrainingPayload {
  prompt: string
  options: string[]
  rounds: Array<{ audio_key: string; answer: string }>
  speed_escalation?: boolean
}

export interface MinimalPairsPayload {
  prompt: string
  pairs: Array<{
    a: { es: string; audio_key: string; gloss: string }
    b: { es: string; audio_key: string; gloss: string }
    prompt_audio: string
    answer: 'a' | 'b'
  }>
}

export interface ShadowRecordPayload {
  prompt: string
  phrases: Array<{ es: string; en: string; audio_key: string }>
}

export interface SoundMatchPayload {
  prompt: string
  rounds: Array<{
    audio_key: string
    es: string
    highlight: string
    options: string[]
    answer: string
  }>
}

export interface SilentLetterTapPayload {
  prompt: string
  words: Array<{
    es: string
    audio_key: string
    silent_indices: number[]
  }>
}

export interface StressTapPayload {
  prompt: string
  words: Array<{
    es: string
    syllables: string[]
    answer_index: number
    audio_key: string
    rule: number
  }>
}

export interface AccentDetectivePayload {
  prompt: string
  rounds: Array<{
    audio_key: string
    options: Array<{ es: string; gloss: string }>
    answer_index: number
  }>
}

export interface RuleSortPayload {
  prompt: string
  buckets: Array<{ key: string; label: string }>
  words: Array<{ es: string; bucket: string }>
}
