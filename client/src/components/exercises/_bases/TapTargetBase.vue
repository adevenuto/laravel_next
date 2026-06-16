<script setup lang="ts">
import { computed, ref } from 'vue'
import { cn } from '@/lib/utils'
import type { ExerciseResult } from '@/types/domain'

interface Target {
  id: string
  label: string
  isAnswer: boolean
}

interface Props {
  prompt: string
  targets: Target[]
  multiple: boolean
  submitLabel?: string
  coaching?: string
}

const props = withDefaults(defineProps<Props>(), {
  submitLabel: 'Next',
  coaching: undefined,
})
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const selected = ref<Set<string>>(new Set())
const settled = ref(false)
const result = ref<{ correct: boolean; score: number } | null>(null)
const coachingMessage = ref<string | null>(null)
const wrongTriedIds = ref<Set<string>>(new Set())

const defaultCoachSingle = 'Not quite — listen again and try a different tile.'
const defaultCoachMulti = "Not that one — those aren't the silent letters here."

const isPerfectSet = computed(() => {
  for (const t of props.targets) {
    if (selected.value.has(t.id) !== t.isAnswer) return false
  }
  return true
})

const canSubmit = computed(() => {
  if (settled.value) return false
  if (!props.multiple) return false
  return isPerfectSet.value
})

function toggle(id: string) {
  if (settled.value) return
  const target = props.targets.find((t) => t.id === id)
  if (!target) return

  if (!props.multiple) {
    if (target.isAnswer) {
      selected.value = new Set([id])
      settled.value = true
      coachingMessage.value = null
      result.value = { correct: true, score: 100 }
      emit('complete', { correct: true, score: 100 })
      return
    }
    // Mastery gate: wrong tap shows the wrong-on tint + coach, never commits.
    // User must keep trying until they find the right tile.
    selected.value = new Set([id])
    wrongTriedIds.value.add(id)
    coachingMessage.value = props.coaching ?? defaultCoachSingle
    return
  }

  const next = new Set(selected.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
    if (!target.isAnswer && coachingMessage.value === null) {
      coachingMessage.value = props.coaching ?? defaultCoachMulti
    }
  }
  selected.value = next
}

function submit() {
  if (!canSubmit.value) return
  const totalTargets = props.targets.length
  let hits = 0
  for (const t of props.targets) {
    const picked = selected.value.has(t.id)
    if (picked === t.isAnswer) hits++
  }
  const score = Math.round((hits / totalTargets) * 100)
  const correct = hits === totalTargets
  settled.value = true
  result.value = { correct, score }
  emit('complete', {
    correct,
    score,
    meta: { hits, total: totalTargets, picked: Array.from(selected.value) },
  })
}

function stateFor(t: Target): 'idle' | 'correct' | 'missed' | 'wrong-on' {
  if (!props.multiple) {
    if (settled.value && t.isAnswer) return 'correct'
    if (wrongTriedIds.value.has(t.id)) return 'wrong-on'
    return 'idle'
  }
  const picked = selected.value.has(t.id)
  if (!settled.value) {
    if (!picked) return 'idle'
    return t.isAnswer ? 'correct' : 'wrong-on'
  }
  if (t.isAnswer && picked) return 'correct'
  if (t.isAnswer && !picked) return 'missed'
  if (!t.isAnswer && picked) return 'wrong-on'
  return 'idle'
}
</script>

<template>
  <div class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Tap it</p>
      <h2 class="text-2xl font-display font-semibold text-foreground leading-tight">
        {{ prompt }}
      </h2>
    </header>

    <div class="flex flex-wrap gap-2">
      <button
        v-for="t in targets"
        :key="t.id"
        type="button"
        :disabled="settled"
        :class="
          cn(
            'es min-h-[3rem] min-w-[3rem] rounded-soft border-2 px-4 py-2 text-xl font-semibold transition-all duration-quick ease-quick',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
            'hover:-translate-y-0.5 active:translate-y-0',
            'disabled:cursor-not-allowed disabled:hover:translate-y-0',
            stateFor(t) === 'idle' && 'border-border bg-card text-foreground',
            stateFor(t) === 'correct' &&
              'border-success bg-success/15 text-foreground animate-correct-pop',
            stateFor(t) === 'wrong-on' &&
              'border-coach bg-coach/15 text-foreground animate-coach-nudge',
            stateFor(t) === 'missed' && 'border-dashed border-coach/70 bg-card text-foreground/60'
          )
        "
        @click="toggle(t.id)"
      >
        {{ t.label }}
      </button>
    </div>

    <p v-if="coachingMessage" class="coach-feedback text-sm">
      {{ coachingMessage }}
    </p>

    <button
      v-if="multiple && !settled"
      type="button"
      :disabled="!canSubmit"
      class="inline-flex h-12 w-full items-center justify-center rounded-soft bg-primary px-6 font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
      @click="submit"
    >
      {{ submitLabel }}
    </button>

    <p v-if="result && result.correct" class="text-sm font-semibold text-success">
      ¡Eso es! Nice tap.
    </p>
  </div>
</template>
