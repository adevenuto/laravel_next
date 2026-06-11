<script setup lang="ts">
import { computed, ref } from 'vue'
import { useFuzzyMatch } from '@/composables/useFuzzyMatch'
import { Input } from '@/components/ui/input'
import { cn } from '@/lib/utils'
import type { ExerciseResult } from '@/types/domain'

interface Props {
  prompt: string
  expected: string[]
  hint?: string
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  hint: '',
  placeholder: '',
})
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const value = ref('')
const lastCoaching = ref<string | null>(null)
const settled = ref(false)
const settledCorrect = ref(false)
const retryCount = ref(0)

const tone = computed<'idle' | 'correct' | 'coach' | 'retry'>(() => {
  if (settled.value && settledCorrect.value) return 'correct'
  if (settled.value) return 'coach'
  if (lastCoaching.value !== null) return 'retry'
  return 'idle'
})

function submit() {
  if (settled.value || value.value.trim() === '') return

  // Best match across the expected forms.
  const matches = props.expected.map((exp) => useFuzzyMatch(exp, value.value))
  const correct = matches.find((m) => m.correct)
  if (correct) {
    settled.value = true
    settledCorrect.value = true
    lastCoaching.value = correct.coaching
    emit('complete', { correct: true, score: 100, meta: { coaching: correct.coaching } })
    return
  }

  const retry = matches.find((m) => m.retry)
  if (retry && retryCount.value < 1) {
    lastCoaching.value = retry.coaching
    retryCount.value++
    return
  }

  settled.value = true
  settledCorrect.value = false
  lastCoaching.value =
    matches.find((m) => m.coaching)?.coaching ?? `Not quite — the answer was ${props.expected[0]}.`
  emit('complete', { correct: false, score: 0, meta: { last_tried: value.value } })
}
</script>

<template>
  <div class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Type it</p>
      <h2 class="text-2xl font-display font-semibold text-foreground leading-tight">
        {{ prompt }}
      </h2>
      <p v-if="hint" class="text-sm text-muted-foreground italic">{{ hint }}</p>
    </header>

    <form class="space-y-3" @submit.prevent="submit">
      <Input
        v-model="value"
        :placeholder="placeholder || 'Type your answer…'"
        :disabled="settled"
        autocomplete="off"
        autocapitalize="off"
        autocorrect="off"
        spellcheck="false"
        :class="
          cn(
            'es h-14 rounded-soft text-xl font-semibold transition-all duration-quick ease-quick',
            tone === 'correct' && 'border-success ring-2 ring-success/40',
            tone === 'coach' && 'border-coach ring-2 ring-coach/30',
            tone === 'retry' && 'border-coach'
          )
        "
      />

      <button
        type="submit"
        :disabled="settled || value.trim().length === 0"
        class="inline-flex h-12 w-full items-center justify-center rounded-soft bg-primary px-6 font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
      >
        Check
      </button>
    </form>

    <p v-if="lastCoaching" class="coach-feedback text-sm">
      {{ lastCoaching }}
    </p>
  </div>
</template>
