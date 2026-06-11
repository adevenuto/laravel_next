import type { InjectionKey, Ref } from 'vue'

export interface PlayFallback {
  /** Spanish text to speak via Web Speech API when no audio sprite exists. */
  es: string
  /** BCP-47 language tag. Default 'es-MX' (Latin American). */
  lang?: string
}

export interface AudioApi {
  play: (key: string, fallback?: PlayFallback) => Promise<void>
  preload: () => Promise<void>
  isSilent: Ref<boolean>
}

/**
 * Per-lesson audio API injected by LessonPlayPage into the exercise tree.
 * Exercises pull it via `inject(audioKey, null)`; null is a valid fallback
 * (the exercise simply skips audio when no provider is present, e.g. in tests).
 */
export const audioKey: InjectionKey<AudioApi> = Symbol('lesson-audio')
