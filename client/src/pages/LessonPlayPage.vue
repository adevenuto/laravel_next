<script setup lang="ts">
import { computed, defineComponent, h, onMounted, provide, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { useLessonStore } from '@/stores/lesson'
import { useLevelMapStore } from '@/stores/levelMap'
import { useUserStore } from '@/stores/me'
import { resolveExerciseComponent } from '@/lib/exerciseRegistry'
import { useAudio } from '@/composables/useAudio'
import { audioKey } from '@/composables/audioInjection'
import LessonResults from '@/pages/LessonPlayPage.results.vue'
import ConfettiBurst from '@/components/ConfettiBurst.vue'
import UnitRewardBurst from '@/components/UnitRewardBurst.vue'
import TeachBody from '@/components/TeachBody.vue'
import { parseTeachMarkdown } from '@/lib/parseTeachMarkdown'
import type { ExerciseResult, LessonCompleteResponse } from '@/types/domain'

const route = useRoute()
const router = useRouter()
const lessonStore = useLessonStore()
const levelMapStore = useLevelMapStore()
const userStore = useUserStore()

type Phase = 'loading' | 'teach' | 'exercise' | 'results' | 'error'

const phase = ref<Phase>('loading')
const teachIndex = ref(0)
const completionResponse = ref<LessonCompleteResponse | null>(null)
// Per-lesson confetti rain fires on every results screen.
const showLessonConfetti = ref(false)
// Sunburst-and-sparks fires additionally when a unit reward lands.
const showUnitReward = ref(false)

const slug = computed(() => String(route.params.slug))
const audio = useAudio(slug.value)
provide(audioKey, audio)

const teachScreens = computed(() => lessonStore.current?.lesson.teach_screens ?? [])
const teachScreen = computed(() => teachScreens.value[teachIndex.value] ?? null)
const totalTeach = computed(() => teachScreens.value.length)
const teachBlocks = computed(() =>
  teachScreen.value ? parseTeachMarkdown(teachScreen.value.body_md) : []
)

// Defensive fallback for unregistered exercise components. Auto-completes so a
// stray non-Phase-2 exercise can't dead-end the lesson.
const ComingSoon = defineComponent({
  emits: ['complete'],
  setup(_, { emit }) {
    onMounted(() => {
      emit('complete', { correct: true, score: 0, meta: { skipped: true } })
    })
    return () =>
      h(
        'div',
        { class: 'text-sm text-muted-foreground italic text-center py-8' },
        'This exercise type ships in a later phase — skipping forward.'
      )
  },
})

function exerciseComponentFor(name: string) {
  return resolveExerciseComponent(name) || ComingSoon
}

async function init() {
  phase.value = 'loading'
  await lessonStore.load(slug.value)

  if (lessonStore.loadError === 'locked') {
    router.replace({ name: 'dashboard', query: { coach: 'locked' } })
    return
  }

  if (lessonStore.loadError) {
    phase.value = 'error'
    return
  }

  audio.preload()
  phase.value = teachScreens.value.length > 0 ? 'teach' : 'exercise'
}

function advanceTeach() {
  if (teachIndex.value < totalTeach.value - 1) {
    teachIndex.value++
  } else {
    phase.value = 'exercise'
  }
}

function backTeach() {
  if (teachIndex.value > 0) {
    teachIndex.value--
  }
}

async function onExerciseComplete(result: ExerciseResult) {
  const ex = lessonStore.currentExercise
  if (!ex) return

  await lessonStore.recordResult(ex.id, result)

  if (lessonStore.isLastExercise) {
    completionResponse.value = await lessonStore.complete(slug.value)
    levelMapStore.invalidate()
    await levelMapStore.fetch(1, true)
    await userStore.fetch()
    phase.value = 'results'

    // Every finished lesson gets the confetti rain. Re-completions and
    // not-actually-final calls won't reach here because of the isLastExercise
    // gate plus the ProgressService idempotency rules.
    showLessonConfetti.value = true

    // Layer the sunburst on top when a fresh unit reward fires — confetti
    // alone for lessons, confetti + sunburst for the bigger unit moment.
    if (completionResponse.value?.reward?.was_new) {
      showUnitReward.value = true
    }
  } else {
    lessonStore.next()
  }
}

function goNext() {
  const nextSlug = completionResponse.value?.next_lesson_slug
  if (nextSlug) {
    lessonStore.reset()
    router.push({ name: 'lesson-play', params: { slug: nextSlug } })
  } else {
    router.push({ name: 'dashboard' })
  }
}

async function retry() {
  lessonStore.reset()
  teachIndex.value = 0
  completionResponse.value = null
  showLessonConfetti.value = false
  showUnitReward.value = false
  await init()
}

onMounted(init)

watch(slug, () => {
  teachIndex.value = 0
  completionResponse.value = null
  init()
})
</script>

<template>
  <div class="mx-auto w-full max-w-2xl py-6 px-4 sm:px-0">
    <!-- Loading state -->
    <div v-if="phase === 'loading'" class="space-y-4 animate-pulse">
      <div class="h-7 w-2/3 rounded-soft bg-muted" />
      <div class="h-40 rounded-kid bg-muted" />
      <div class="h-12 w-full rounded-soft bg-muted" />
    </div>

    <!-- Error state -->
    <div v-else-if="phase === 'error'" class="sunlit-card p-8 space-y-4 text-center">
      <h2 class="font-display text-2xl font-semibold">Couldn't load this lesson</h2>
      <p class="text-sm text-muted-foreground">
        Something went wrong. Try again from the dashboard.
      </p>
      <button
        type="button"
        class="inline-flex h-12 items-center justify-center rounded-soft bg-primary px-6 font-semibold text-primary-foreground"
        @click="router.push({ name: 'dashboard' })"
      >
        Back to dashboard
      </button>
    </div>

    <!-- Teach screens -->
    <div v-else-if="phase === 'teach' && teachScreen" class="space-y-6">
      <div
        class="flex items-center justify-between text-xs text-muted-foreground uppercase tracking-wider"
      >
        <span>{{ lessonStore.current?.lesson.unit.title }}</span>
        <span>{{ teachIndex + 1 }} / {{ totalTeach }}</span>
      </div>

      <article class="sunlit-card p-6 sm:p-8 space-y-4">
        <h1 class="font-display text-3xl font-semibold leading-tight">
          {{ teachScreen.title }}
        </h1>
        <TeachBody class="es text-lg leading-relaxed text-foreground/90" :blocks="teachBlocks" />
      </article>

      <div class="flex gap-2">
        <button
          v-if="teachIndex > 0"
          type="button"
          class="inline-flex h-14 shrink-0 items-center justify-center gap-1.5 rounded-soft border-2 border-border bg-card px-5 text-base font-semibold text-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          aria-label="Previous teach screen"
          @click="backTeach"
        >
          <ChevronLeft class="h-5 w-5" />
          <span class="hidden sm:inline">Back</span>
        </button>
        <button
          type="button"
          class="inline-flex h-14 flex-1 items-center justify-center gap-2 rounded-soft bg-primary px-6 text-lg font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          @click="advanceTeach"
        >
          {{ teachIndex < totalTeach - 1 ? 'Continue' : 'Start practice' }}
          <ChevronRight class="h-5 w-5" />
        </button>
      </div>
    </div>

    <!-- Exercise queue -->
    <div v-else-if="phase === 'exercise' && lessonStore.currentExercise" class="space-y-6">
      <div
        class="flex items-center justify-between text-xs text-muted-foreground uppercase tracking-wider"
      >
        <span>{{ lessonStore.current?.lesson.title }}</span>
        <span>
          Exercise {{ lessonStore.currentExerciseIndex + 1 }} /
          {{ lessonStore.current?.exercises.length }}
        </span>
      </div>

      <div class="sunlit-card p-6 sm:p-8">
        <component
          :is="exerciseComponentFor(lessonStore.currentExercise.component)"
          :key="lessonStore.currentExercise.id"
          :payload="lessonStore.currentExercise.payload"
          @complete="onExerciseComplete"
        />
      </div>
    </div>

    <!-- Results -->
    <LessonResults
      v-else-if="phase === 'results' && lessonStore.current"
      :lesson-title="lessonStore.current.lesson.title"
      :results="lessonStore.results"
      :completion="completionResponse"
      @next="goNext"
      @retry="retry"
    />

    <!-- Per-lesson confetti rain — fires on every results screen. -->
    <ConfettiBurst :active="showLessonConfetti" @done="showLessonConfetti = false" />

    <!-- Bigger sunburst that layers on top when a unit reward fires. -->
    <UnitRewardBurst :active="showUnitReward" @done="showUnitReward = false" />
  </div>
</template>
