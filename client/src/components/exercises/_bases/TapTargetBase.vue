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
}

const props = withDefaults(defineProps<Props>(), { submitLabel: 'Check' })
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const selected = ref<Set<string>>(new Set())
const settled = ref(false)
const result = ref<{ correct: boolean; score: number } | null>(null)

const canSubmit = computed(() => {
  if (settled.value) return false
  if (!props.multiple) return selected.value.size === 1
  return true
})

function toggle(id: string) {
  if (settled.value) return
  if (!props.multiple) {
    selected.value = new Set([id])
    return
  }
  const next = new Set(selected.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
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

function stateFor(t: Target): 'idle' | 'on' | 'correct' | 'missed' | 'wrong-on' {
  if (!settled.value) {
    return selected.value.has(t.id) ? 'on' : 'idle'
  }
  if (t.isAnswer && selected.value.has(t.id)) return 'correct'
  if (t.isAnswer && !selected.value.has(t.id)) return 'missed'
  if (!t.isAnswer && selected.value.has(t.id)) return 'wrong-on'
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
            stateFor(t) === 'on' && 'border-primary bg-primary/15 text-foreground',
            stateFor(t) === 'correct' &&
              'border-success bg-success/15 text-foreground animate-correct-pop',
            stateFor(t) === 'wrong-on' && 'border-coach bg-coach/15 text-foreground',
            stateFor(t) === 'missed' && 'border-dashed border-coach/70 bg-card text-foreground/60'
          )
        "
        @click="toggle(t.id)"
      >
        {{ t.label }}
      </button>
    </div>

    <button
      v-if="!settled"
      type="button"
      :disabled="!canSubmit"
      class="inline-flex h-12 w-full items-center justify-center rounded-soft bg-primary px-6 font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
      @click="submit"
    >
      {{ submitLabel }}
    </button>

    <p v-if="result && result.correct" class="text-sm font-semibold text-success">
      ¡Perfecto! All of them.
    </p>
    <p v-else-if="result && !result.correct" class="coach-feedback text-sm">
      So close — review which were the right taps and try again next time.
    </p>
  </div>
</template>
