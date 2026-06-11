import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchMe } from '@/lib/lessonsApi'
import type { Badge, FeatureFlags, MeProfile, Streak } from '@/types/me'
import type { LessonCompleteReward } from '@/types/domain'

/**
 * Gameplay profile store. Consumes /api/me (richer than /api/user) for XP,
 * vocab counter, streak, badges, and feature flags. Lives in PARALLEL to the
 * auth store — auth boot keeps using /api/user untouched (Phase 0 decision).
 */
export const useUserStore = defineStore('me', () => {
  const profile = ref<MeProfile | null>(null)
  const isLoading = ref(false)

  const xp = computed(() => profile.value?.xp ?? 0)
  const vocabCounter = computed(() => profile.value?.vocab_counter ?? 0)
  const flags = computed<FeatureFlags>(() => profile.value?.feature_flags ?? {})
  const badges = computed<Badge[]>(() => profile.value?.badges ?? [])
  const streak = computed<Streak>(
    () => profile.value?.streak ?? { count: 0, last_active_date: null }
  )

  async function fetch() {
    isLoading.value = true
    try {
      profile.value = await fetchMe()
    } finally {
      isLoading.value = false
    }
  }

  function applyXpDelta(delta: number) {
    if (!profile.value || delta === 0) return
    profile.value = { ...profile.value, xp: profile.value.xp + delta }
  }

  function applyVocabDelta(delta: number) {
    if (!profile.value || delta === 0) return
    profile.value = { ...profile.value, vocab_counter: profile.value.vocab_counter + delta }
  }

  /**
   * Apply a unit-reward descriptor optimistically after a lesson completion.
   * The next /api/me fetch reconciles to server truth.
   */
  function applyReward(reward: LessonCompleteReward | null) {
    if (!reward || !reward.was_new || !profile.value) return

    applyVocabDelta(reward.vocab_delta)

    if (reward.feature_flag) {
      profile.value = {
        ...profile.value,
        feature_flags: { ...profile.value.feature_flags, [reward.feature_flag]: true },
      }
    }
  }

  function clear() {
    profile.value = null
  }

  return {
    profile,
    isLoading,
    xp,
    vocabCounter,
    flags,
    badges,
    streak,
    fetch,
    applyXpDelta,
    applyVocabDelta,
    applyReward,
    clear,
  }
})
