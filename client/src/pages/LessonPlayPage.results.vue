<script setup lang="ts">
import { computed } from 'vue'
import { CheckCircle2, RotateCcw, Sparkles } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import type { ExerciseResult, LessonCompleteResponse } from '@/types/domain'

interface Props {
  lessonTitle: string
  results: Array<{ exerciseId: number; result: ExerciseResult }>
  completion: LessonCompleteResponse | null
}

const props = defineProps<Props>()
const emit = defineEmits<{ next: []; retry: [] }>()

const correctCount = computed(() => props.results.filter((r) => r.result.correct).length)
const total = computed(() => props.results.length)
const percent = computed(() =>
  total.value === 0 ? 0 : Math.round((correctCount.value / total.value) * 100)
)

const xpEarned = computed(() => {
  // Sum of exercise xp values would require knowing each exercise's xp; the
  // lesson store tracks attempt POST responses which include xp_earned. For
  // Phase 2 we infer from result.correct against a flat 10 xp default — the
  // server is the actual source of truth (totaled in /api/me).
  return props.results.filter((r) => r.result.correct).length * 10
})

const ctaLabel = computed(() => {
  if (!props.completion) return 'Saving…'
  if (props.completion.next_lesson_slug) return 'Next lesson →'
  return 'Back to dashboard'
})

const hasReward = computed(() => Boolean(props.completion?.reward?.was_new))

// Promote "Try again" to primary visual weight when score is imperfect so the
// invitation to practice reads as the obvious next step. At 100% the next/dashboard
// CTA stays primary and "Try again" is the quiet secondary.
const isPerfect = computed(() => percent.value === 100)
</script>

<template>
  <div class="space-y-8">
    <header class="space-y-3 text-center">
      <CheckCircle2 class="mx-auto h-16 w-16 text-success" />
      <h1 class="font-display text-4xl font-semibold text-foreground">¡Lección terminada!</h1>
      <p class="text-muted-foreground">{{ lessonTitle }}</p>
    </header>

    <div class="sunlit-card p-8 space-y-6">
      <div class="grid grid-cols-2 gap-6">
        <div class="space-y-1">
          <div class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
            Score
          </div>
          <div class="numeric-display text-5xl text-foreground">{{ percent }}%</div>
          <div class="text-xs text-muted-foreground">{{ correctCount }}/{{ total }} correct</div>
        </div>
        <div class="space-y-1">
          <div class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
            XP earned
          </div>
          <div class="numeric-display text-5xl text-primary">+{{ xpEarned }}</div>
        </div>
      </div>

      <!-- Per-exercise dots -->
      <div class="flex flex-wrap gap-1.5">
        <span
          v-for="(r, i) in results"
          :key="i"
          :class="cn('h-2.5 w-8 rounded-pill', r.result.correct ? 'bg-success' : 'bg-coach/60')"
        />
      </div>
    </div>

    <!-- Reward fired? -->
    <div v-if="hasReward" class="sunlit-card p-6 space-y-2 border-2 border-primary/30">
      <div class="flex items-center gap-2">
        <Sparkles class="h-5 w-5 text-primary" />
        <span class="text-sm font-semibold text-primary uppercase tracking-wider">
          Unit reward unlocked
        </span>
      </div>
      <p class="text-foreground">
        You earned the
        <strong class="font-display">{{ completion?.reward?.badge_key }}</strong>
        badge.
      </p>
      <p v-if="completion?.reward?.feature_flag" class="text-sm text-muted-foreground">
        New ability unlocked: {{ completion.reward.feature_flag }}
      </p>
      <p
        v-if="completion && completion.reward && completion.reward.vocab_delta > 0"
        class="text-sm"
      >
        <strong>+{{ completion.reward.vocab_delta }}</strong> to your Spanish vocabulary.
      </p>
    </div>

    <div class="flex flex-col gap-3">
      <button
        type="button"
        :class="
          cn(
            'inline-flex h-14 w-full items-center justify-center rounded-soft text-lg font-semibold transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
            isPerfect
              ? 'bg-primary text-primary-foreground'
              : 'border-2 border-border bg-card text-foreground'
          )
        "
        @click="emit('next')"
      >
        {{ ctaLabel }}
      </button>

      <button
        type="button"
        :class="
          cn(
            'inline-flex h-14 w-full items-center justify-center gap-2 rounded-soft text-lg font-semibold transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
            isPerfect
              ? 'border-2 border-border bg-card text-foreground'
              : 'bg-primary text-primary-foreground'
          )
        "
        @click="emit('retry')"
      >
        <RotateCcw class="h-5 w-5" />
        Try again
      </button>
    </div>
  </div>
</template>
