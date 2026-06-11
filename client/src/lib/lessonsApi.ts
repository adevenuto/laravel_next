import { api, ensureCsrfCookie } from '@/lib/api'
import type { MeProfile } from '@/types/me'
import type {
  AttemptResponse,
  ExerciseResult,
  LessonCompleteResponse,
  LessonPlayResponse,
  LevelMapResponse,
} from '@/types/domain'

interface Wrapped<T> {
  data: T
}

function unwrap<T>(payload: T | Wrapped<T>): T {
  if (payload && typeof payload === 'object' && 'data' in payload) {
    return (payload as Wrapped<T>).data
  }
  return payload as T
}

export async function fetchMe(): Promise<MeProfile> {
  const { data } = await api.get<Wrapped<MeProfile>>('/api/me')
  return unwrap(data)
}

export async function fetchLevelMap(levelNumber: number): Promise<LevelMapResponse> {
  const { data } = await api.get<Wrapped<LevelMapResponse>>(`/api/levels/${levelNumber}/map`)
  return unwrap(data)
}

export async function fetchLessonPlay(slug: string): Promise<LessonPlayResponse> {
  const { data } = await api.get<Wrapped<LessonPlayResponse>>(`/api/lessons/${slug}`)
  return unwrap(data)
}

export async function submitAttempt(
  exerciseId: number,
  result: ExerciseResult
): Promise<AttemptResponse> {
  await ensureCsrfCookie()
  const { data } = await api.post<AttemptResponse>(`/api/exercises/${exerciseId}/attempt`, {
    correct: result.correct,
    score: result.score,
    meta: result.meta ?? null,
  })
  return data
}

export async function completeLesson(
  slug: string,
  score?: number
): Promise<LessonCompleteResponse> {
  await ensureCsrfCookie()
  const body = score === undefined ? {} : { score }
  const { data } = await api.post<LessonCompleteResponse>(`/api/lessons/${slug}/complete`, body)
  return data
}

export async function updateProfile(displayName: string, hometown: string): Promise<MeProfile> {
  await ensureCsrfCookie()
  const { data } = await api.post<Wrapped<MeProfile>>('/api/profile/onboarding', {
    display_name: displayName,
    hometown: hometown,
  })
  return unwrap(data)
}
