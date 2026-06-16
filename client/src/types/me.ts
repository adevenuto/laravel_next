export interface Badge {
  key: string
  title: string
  description: string
  icon: string
  earned_at: string | null
}

export interface Streak {
  count: number
  last_active_date: string | null
}

export type FeatureFlags = Record<string, boolean>

export interface MeProfile {
  id: number
  first_name: string
  last_name: string
  email: string
  display_name: string | null
  hometown: string | null
  xp: number
  vocab_counter: number
  streak: Streak
  badges: Badge[]
  feature_flags: FeatureFlags
  /** DEV-UNLOCK: true when the dev "Mark all complete" toggle is currently in
   *  its unlocked state (a snapshot of the user's real progress is stored on
   *  the user row, waiting to be restored). Not present in production responses
   *  in any meaningful way — the endpoints are gated to local/testing. */
  dev_snapshot_present: boolean
}
