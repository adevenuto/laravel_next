<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Eraser,
  Flame,
  KeyRound,
  Lock,
  PartyPopper,
  Sparkles,
  Trophy,
  Undo2,
  Zap,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { useLevelMapStore } from '@/stores/levelMap'
import { useUserStore } from '@/stores/me'
import ConfettiBurst from '@/components/ConfettiBurst.vue'
import UnitRewardBurst from '@/components/UnitRewardBurst.vue'
import * as devApi from '@/lib/devApi'
import type { LessonSummary } from '@/types/domain'

const route = useRoute()
const router = useRouter()
const levelMap = useLevelMapStore()
const me = useUserStore()

const showLockedToast = ref(route.query.coach === 'locked')

// DEV: temporary celebration testers — preview both effects without finishing
// a lesson. Remove these and the buttons in the template when you no longer
// want to preview the celebrations. Marked with DEV-CONFETTI for easy grep.
const showDevConfetti = ref(false)
const showDevUnitReward = ref(false)

// DEV-UNLOCK: temporary play-through shortcuts. Marked DEV-UNLOCK for cleanup.
const devStatus = ref<string | null>(null)
const devBusy = ref(false)

const isUnlockedState = computed(() => Boolean(me.profile?.dev_snapshot_present))

async function devToggleUnlock() {
  if (devBusy.value) return
  devBusy.value = true
  try {
    const res = await devApi.toggleCompleteAll()
    // eslint-disable-next-line no-console
    console.debug('[DEV-UNLOCK] toggle response', res)
    levelMap.invalidate()
    await Promise.all([levelMap.fetch(1, true), me.fetch()])
    // eslint-disable-next-line no-console
    console.debug(
      '[DEV-UNLOCK] post-refetch map first-unit lock states',
      levelMap.units[0]?.lessons.map((l) => `${l.slug}=${l.lock_state}`)
    )
    flashStatus(res.state === 'unlocked' ? 'Unlocked.' : 'Progress restored.')
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('[DEV-UNLOCK] toggle failed', err)
    flashStatus('Toggle FAILED — see console')
  } finally {
    devBusy.value = false
  }
}

async function devReset() {
  if (devBusy.value) return
  devBusy.value = true
  try {
    await devApi.resetProgress()
    levelMap.invalidate()
    await Promise.all([levelMap.fetch(1, true), me.fetch()])
    flashStatus('Reset.')
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('[DEV-UNLOCK] reset failed', err)
    flashStatus('Reset FAILED — see console')
  } finally {
    devBusy.value = false
  }
}

function flashStatus(label: string) {
  devStatus.value = label
  setTimeout(() => (devStatus.value = null), 2500)
}

onMounted(async () => {
  await Promise.all([levelMap.fetch(1), me.fetch()])
  if (showLockedToast.value) {
    setTimeout(() => (showLockedToast.value = false), 4500)
    router.replace({ query: {} })
  }
})

const isLoading = computed(() => levelMap.isLoading && levelMap.map === null)

function goToLesson(lesson: LessonSummary) {
  if (lesson.lock_state === 'locked') return
  router.push({ name: 'lesson-play', params: { slug: lesson.slug } })
}

function firstNameOrYou() {
  return me.profile?.first_name ?? 'you'
}
</script>

<template>
  <div class="space-y-10">
    <!-- Coach-style 403 toast (set when LessonPlayPage redirects on locked) -->
    <Transition
      enter-active-class="transition duration-quick ease-out"
      enter-from-class="-translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-quick ease-out"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showLockedToast"
        class="coach-feedback flex items-start gap-3 text-sm"
        role="status"
      >
        <Lock class="h-4 w-4 mt-0.5 flex-shrink-0" />
        <p>That one's still locked — finish the previous lesson first.</p>
      </div>
    </Transition>

    <!-- Hero strip -->
    <header class="space-y-5">
      <div class="space-y-1">
        <p class="text-sm uppercase tracking-wider text-muted-foreground font-semibold">
          ¡Hola, {{ firstNameOrYou() }}!
        </p>
        <h1 class="font-display text-4xl sm:text-5xl font-semibold leading-tight">
          Level 1 — Survival &amp; Sound System
        </h1>
        <p class="text-muted-foreground max-w-prose">
          {{
            levelMap.map?.level.promise_text ??
            'Five units. Eighteen lessons. One boss conversation.'
          }}
        </p>
      </div>

      <div class="grid grid-cols-3 gap-3 sm:max-w-2xl">
        <div class="sunlit-card p-4 sm:p-5 space-y-1">
          <div
            class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground font-semibold"
          >
            <Trophy class="h-3.5 w-3.5" />
            Vocab
          </div>
          <div class="numeric-display text-3xl sm:text-5xl text-foreground">
            {{ me.vocabCounter }}
          </div>
          <div class="text-xs text-muted-foreground">words you can use</div>
        </div>

        <div class="sunlit-card p-4 sm:p-5 space-y-1">
          <div
            class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground font-semibold"
          >
            <Zap class="h-3.5 w-3.5" />
            XP
          </div>
          <div class="numeric-display text-3xl sm:text-5xl text-primary">
            {{ me.xp }}
          </div>
          <div class="text-xs text-muted-foreground">total earned</div>
        </div>

        <div class="sunlit-card p-4 sm:p-5 space-y-1">
          <div
            class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground font-semibold"
          >
            <Flame class="h-3.5 w-3.5" />
            Streak
          </div>
          <div class="numeric-display text-3xl sm:text-5xl text-foreground/40">
            {{ me.streak.count }}
          </div>
          <div class="text-xs text-muted-foreground italic">soon…</div>
        </div>
      </div>

      <!-- DEV-CONFETTI: temporary triggers. Remove these buttons + the refs +
           the <ConfettiBurst /> + <UnitRewardBurst /> mounts below to clean up. -->
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 self-start rounded-pill border border-dashed border-coach/60 bg-coach/10 px-3 py-1 text-xs font-semibold text-coach-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          @click="showDevConfetti = true"
        >
          <PartyPopper class="h-3.5 w-3.5" />
          DEV · Lesson confetti
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 self-start rounded-pill border border-dashed border-primary/60 bg-primary/10 px-3 py-1 text-xs font-semibold text-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          @click="showDevUnitReward = true"
        >
          <PartyPopper class="h-3.5 w-3.5" />
          DEV · Unit reward burst
        </button>

        <!-- DEV-UNLOCK: play-through shortcuts. Backend is env-gated to local/testing.
             The toggle snapshots real progress into users.progress_snapshot when
             unlocking, and restores from it on the next click. -->
        <button
          type="button"
          :disabled="devBusy"
          :class="[
            'inline-flex items-center gap-1.5 self-start rounded-pill border border-dashed px-3 py-1 text-xs font-semibold transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0',
            isUnlockedState
              ? 'border-primary/60 bg-primary/10 text-foreground'
              : 'border-success/60 bg-success/10 text-foreground',
          ]"
          @click="devToggleUnlock"
        >
          <component :is="isUnlockedState ? Undo2 : KeyRound" class="h-3.5 w-3.5" />
          {{ isUnlockedState ? 'DEV · Restore my progress' : 'DEV · Mark all complete' }}
        </button>
        <button
          type="button"
          :disabled="devBusy"
          class="inline-flex items-center gap-1.5 self-start rounded-pill border border-dashed border-muted-foreground/60 bg-muted px-3 py-1 text-xs font-semibold text-muted-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
          @click="devReset"
        >
          <Eraser class="h-3.5 w-3.5" />
          DEV · Reset progress
        </button>

        <span
          v-if="devStatus"
          class="self-start text-xs font-semibold text-success px-2 py-1"
          role="status"
        >
          {{ devStatus }}
        </span>
      </div>
    </header>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-6">
      <div v-for="i in 3" :key="i" class="space-y-3 animate-pulse">
        <div class="h-5 w-1/3 rounded-soft bg-muted" />
        <div class="grid gap-3 sm:grid-cols-3">
          <div v-for="j in 3" :key="j" class="h-32 rounded-kid bg-muted" />
        </div>
      </div>
    </div>

    <!-- Units -->
    <div v-else class="space-y-10">
      <section v-for="(unit, unitIdx) in levelMap.units" :key="unit.id" class="space-y-4">
        <header class="space-y-1">
          <p class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
            Unit 1.{{ unitIdx + 1 }}
          </p>
          <h2 class="font-display text-2xl font-semibold leading-tight">
            {{ unit.title }}
          </h2>
          <p class="text-sm italic text-muted-foreground">{{ unit.tagline }}</p>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <button
            v-for="(lesson, lessonIdx) in unit.lessons"
            :key="lesson.id"
            type="button"
            :disabled="lesson.lock_state === 'locked'"
            :class="
              cn(
                'group relative p-5 text-left transition-all duration-quick ease-quick',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                lesson.lock_state === 'locked'
                  ? 'sunlit-card-locked cursor-not-allowed'
                  : 'sunlit-card sunlit-card-interactive'
              )
            "
            @click="goToLesson(lesson)"
          >
            <div class="flex items-start justify-between gap-3 mb-3">
              <span
                class="numeric-display text-3xl"
                :class="
                  lesson.lock_state === 'completed'
                    ? 'text-success'
                    : lesson.lock_state === 'available'
                      ? 'text-primary'
                      : 'text-muted-foreground'
                "
              >
                {{ unitIdx + 1 }}.{{ lessonIdx + 1 }}
              </span>

              <span v-if="lesson.lock_state === 'completed'" class="chip chip-jade">
                <Sparkles class="h-3 w-3" />
                {{ lesson.best_score !== null ? `${lesson.best_score}%` : 'Done' }}
              </span>
              <Lock
                v-else-if="lesson.lock_state === 'locked'"
                class="h-4 w-4 text-muted-foreground mt-1"
              />
            </div>

            <h3 class="font-display text-lg font-semibold leading-tight">
              {{ lesson.title }}
            </h3>
          </button>
        </div>
      </section>
    </div>

    <!-- DEV-CONFETTI: temporary preview overlays. -->
    <ConfettiBurst :active="showDevConfetti" @done="showDevConfetti = false" />
    <UnitRewardBurst :active="showDevUnitReward" @done="showDevUnitReward = false" />
  </div>
</template>
