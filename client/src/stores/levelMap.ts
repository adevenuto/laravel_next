import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchLevelMap } from '@/lib/lessonsApi'
import type { LessonSummary, LevelMapResponse } from '@/types/domain'

const CACHE_TTL_MS = 10 * 60 * 1000

export const useLevelMapStore = defineStore('levelMap', () => {
  const map = ref<LevelMapResponse | null>(null)
  const isLoading = ref(false)
  const lastFetchedAt = ref<number | null>(null)

  const units = computed(() => map.value?.units ?? [])
  const allLessons = computed<LessonSummary[]>(() => units.value.flatMap((u) => u.lessons))

  /**
   * Fetch the level map. Skips the request when a recent cached version exists,
   * unless `force=true` is passed (used after lesson completion).
   */
  async function fetch(levelNumber = 1, force = false) {
    const cacheFresh =
      !force &&
      map.value !== null &&
      lastFetchedAt.value !== null &&
      Date.now() - lastFetchedAt.value < CACHE_TTL_MS

    if (cacheFresh) return

    isLoading.value = true
    try {
      map.value = await fetchLevelMap(levelNumber)
      lastFetchedAt.value = Date.now()
    } finally {
      isLoading.value = false
    }
  }

  function invalidate() {
    lastFetchedAt.value = null
  }

  function lessonBySlug(slug: string): LessonSummary | null {
    return allLessons.value.find((l) => l.slug === slug) ?? null
  }

  function clear() {
    map.value = null
    lastFetchedAt.value = null
  }

  return {
    map,
    isLoading,
    lastFetchedAt,
    units,
    allLessons,
    fetch,
    invalidate,
    lessonBySlug,
    clear,
  }
})
