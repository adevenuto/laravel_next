export type LockState = 'locked' | 'available' | 'completed'

export interface LessonSummary {
  id: number
  slug: string
  title: string
  order: number
  skill_key: string
  lock_state: LockState
  best_score: number | null
  completed_at: string | null
}

export interface UnitSummary {
  id: number
  slug: string
  title: string
  tagline: string
  order: number
  reward_badge_key: string
  reward_feature_key: string | null
  lessons: LessonSummary[]
}

export interface LevelSummary {
  id: number
  number: number
  slug: string
  title: string
  promise_text: string
}

export interface LevelMapResponse {
  level: LevelSummary
  units: UnitSummary[]
}

export type TeachScreenType = 'concept' | 'example' | 'callout' | 'audio_demo'

export interface TeachScreen {
  type: TeachScreenType
  title: string
  body_md: string
  audio_key?: string
  visual_key?: string
}

export interface ExerciseRow {
  id: number
  component: string
  payload: Record<string, unknown>
  order: number
  xp: number
}

export interface LessonPlayResponse {
  lesson: {
    id: number
    slug: string
    title: string
    order: number
    skill_key: string
    teach_screens: TeachScreen[]
    unit: { slug: string; title: string }
  }
  exercises: ExerciseRow[]
}

export interface ExerciseResult {
  correct: boolean
  score: number
  meta?: Record<string, unknown>
}

export interface AttemptResponse {
  xp_earned: number
  xp_total: number
  mastery: {
    skill_key: string
    tier: 'recognize' | 'transform' | 'produce' | 'combine' | 'spontaneous'
    srs_due_at: string | null
    srs_interval_days: number
  }
}

export interface LessonCompleteReward {
  was_new: boolean
  badge_key: string
  vocab_delta: number
  feature_flag: string | null
}

export interface LessonCompleteResponse {
  progress: {
    status: 'completed'
    best_score: number | null
    completed_at: string | null
  }
  reward: LessonCompleteReward | null
  next_lesson_slug: string | null
}
