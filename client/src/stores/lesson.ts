import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { completeLesson, fetchLessonPlay, submitAttempt } from '@/lib/lessonsApi'
import { useUserStore } from '@/stores/me'
import type {
  ExerciseResult,
  ExerciseRow,
  LessonCompleteResponse,
  LessonPlayResponse,
} from '@/types/domain'

interface ResultRow {
  exerciseId: number
  result: ExerciseResult
}

export const useLessonStore = defineStore('lesson', () => {
  const current = ref<LessonPlayResponse | null>(null)
  const currentExerciseIndex = ref(0)
  const results = ref<ResultRow[]>([])
  const isLoading = ref(false)
  const loadError = ref<'not_found' | 'locked' | 'unknown' | null>(null)

  const currentExercise = computed<ExerciseRow | null>(() => {
    const lesson = current.value
    if (!lesson) return null
    return lesson.exercises[currentExerciseIndex.value] ?? null
  })

  const isLastExercise = computed(() => {
    const lesson = current.value
    if (!lesson) return false
    return currentExerciseIndex.value >= lesson.exercises.length - 1
  })

  const finalScore = computed(() => {
    if (results.value.length === 0) return 0
    const correct = results.value.filter((r) => r.result.correct).length
    return Math.round((correct / results.value.length) * 100)
  })

  async function load(slug: string) {
    isLoading.value = true
    loadError.value = null
    currentExerciseIndex.value = 0
    results.value = []

    try {
      current.value = await fetchLessonPlay(slug)
    } catch (err: unknown) {
      const status =
        typeof err === 'object' && err !== null && 'response' in err
          ? (err as { response?: { status?: number } }).response?.status
          : undefined
      loadError.value = status === 403 ? 'locked' : status === 404 ? 'not_found' : 'unknown'
      current.value = null
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Record a per-exercise result locally AND fire-and-forget the server attempt.
   * Returns the server's mastery info so the caller can show coaching if needed.
   */
  async function recordResult(exerciseId: number, result: ExerciseResult) {
    results.value.push({ exerciseId, result })

    try {
      const response = await submitAttempt(exerciseId, result)
      const userStore = useUserStore()
      userStore.applyXpDelta(response.xp_earned)
      return response
    } catch {
      // Attempt POST is best-effort; results stay in local state. The user can
      // continue. Background sync handled in Phase 4.
      return null
    }
  }

  function next() {
    if (current.value && currentExerciseIndex.value < current.value.exercises.length - 1) {
      currentExerciseIndex.value++
    }
  }

  function reset() {
    current.value = null
    currentExerciseIndex.value = 0
    results.value = []
    loadError.value = null
  }

  async function complete(slug: string): Promise<LessonCompleteResponse | null> {
    try {
      const response = await completeLesson(slug, finalScore.value)
      const userStore = useUserStore()
      userStore.applyReward(response.reward)
      return response
    } catch {
      return null
    }
  }

  return {
    current,
    currentExerciseIndex,
    results,
    isLoading,
    loadError,
    currentExercise,
    isLastExercise,
    finalScore,
    load,
    recordResult,
    next,
    reset,
    complete,
  }
})
