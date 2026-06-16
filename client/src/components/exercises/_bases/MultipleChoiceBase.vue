<script setup lang="ts" generic="V">
import { computed, ref } from 'vue'
import { Volume2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import type { ExerciseResult } from '@/types/domain'

interface Option<V> {
  label: string
  value: V
  sublabel?: string
  audioKey?: string
}

interface Props {
  prompt: string
  options: Array<Option<V>>
  correctValue: V
  promptAudioKey?: string
  coaching?: string
  /**
   * Per-option coach overrides keyed by `String(option.value)`. When a user picks
   * a wrong option whose key is in this map, the message wins over the generic
   * `coaching` default. Used by ChooseTheReal to surface the seeded `why_wrong`
   * coach prose per distractor.
   */
  coachingPerValue?: Record<string, string>
  requirePerfect?: boolean
  playPromptAudio?: (key: string) => void
  playOptionAudio?: (key: string) => void
}

const props = withDefaults(defineProps<Props>(), {
  promptAudioKey: undefined,
  coaching: undefined,
  coachingPerValue: undefined,
  requirePerfect: false,
  playPromptAudio: undefined,
  playOptionAudio: undefined,
})

function coachFor(value: V, fallback: string): string {
  const perValue = props.coachingPerValue?.[String(value)]
  return perValue ?? props.coaching ?? fallback
}
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const chosenValue = ref<V | null>(null)
const lastTriedValue = ref<V | null>(null)
const wrongTriedKeys = ref<Set<string>>(new Set())
const settled = ref(false)
const coachingMessage = ref<string | null>(null)

const isCorrect = computed(() => chosenValue.value === props.correctValue)

function choose(value: V) {
  if (settled.value) return
  lastTriedValue.value = value

  if (value === props.correctValue) {
    chosenValue.value = value
    settled.value = true
    coachingMessage.value = null
    emit('complete', { correct: true, score: 100 })
    return
  }

  // Mastery gate: any wrong tap shows the coach + persistent wrong-on tint and waits
  // for the user to try a different option. Never commits.
  if (props.requirePerfect) {
    wrongTriedKeys.value.add(String(value))
    coachingMessage.value = coachFor(value, 'Not quite — listen again and try a different one.')
    return
  }

  // Legacy single-retry: first wrong → coach, allow one retry. Second wrong → record.
  if (coachingMessage.value === null) {
    coachingMessage.value = coachFor(value, 'Not quite — listen again, then try once more.')
  } else {
    chosenValue.value = value
    settled.value = true
    emit('complete', { correct: false, score: 0, meta: { last_tried: String(value) } })
  }
}

function stateFor(value: V): 'idle' | 'correct' | 'coach' {
  if (settled.value && value === props.correctValue) return 'correct'
  if (props.requirePerfect) {
    if (wrongTriedKeys.value.has(String(value))) return 'coach'
    return 'idle'
  }
  if (!settled.value && lastTriedValue.value === value && coachingMessage.value !== null) {
    return 'coach'
  }
  return 'idle'
}

function handlePromptAudio() {
  if (props.promptAudioKey && props.playPromptAudio) {
    props.playPromptAudio(props.promptAudioKey)
  }
}

function handleOptionAudio(option: Option<V>) {
  if (option.audioKey && props.playOptionAudio) {
    props.playOptionAudio(option.audioKey)
  }
}
</script>

<template>
  <div class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Listen</p>
      <h2 class="text-2xl font-display font-semibold text-foreground leading-tight">
        {{ prompt }}
      </h2>

      <button
        v-if="promptAudioKey"
        type="button"
        class="mt-3 inline-flex items-center gap-2 rounded-pill bg-secondary px-4 py-2 text-secondary-foreground font-semibold text-sm transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
        @click="handlePromptAudio"
      >
        <Volume2 class="h-4 w-4" />
        Hear it
      </button>
    </header>

    <div class="grid gap-3 sm:grid-cols-2">
      <button
        v-for="option in options"
        :key="String(option.value)"
        type="button"
        :disabled="settled"
        :class="
          cn(
            'group relative flex min-h-[3.25rem] items-center justify-between rounded-soft border-2 bg-card px-5 py-4 text-left transition-all duration-quick ease-quick',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
            'hover:-translate-y-0.5 hover:shadow-lifted active:translate-y-0 active:shadow-raised',
            'disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-raised',
            stateFor(option.value) === 'correct' &&
              'border-success bg-success/10 animate-correct-pop',
            stateFor(option.value) === 'coach' && 'border-coach bg-coach/10 animate-coach-nudge',
            stateFor(option.value) === 'idle' && 'border-border'
          )
        "
        @click="choose(option.value)"
      >
        <span class="flex-1">
          <span class="es block text-xl font-semibold text-foreground">{{ option.label }}</span>
          <span
            v-if="option.sublabel"
            class="block text-sm text-muted-foreground font-normal mt-0.5"
          >
            {{ option.sublabel }}
          </span>
        </span>

        <span
          v-if="option.audioKey"
          class="ml-3 inline-flex h-9 w-9 items-center justify-center rounded-pill bg-muted text-muted-foreground transition-colors group-hover:bg-secondary group-hover:text-secondary-foreground"
          role="button"
          tabindex="-1"
          aria-label="Hear this option"
          @click.stop="handleOptionAudio(option)"
        >
          <Volume2 class="h-4 w-4" />
        </span>
      </button>
    </div>

    <p v-if="coachingMessage" class="coach-feedback text-sm">
      {{ coachingMessage }}
    </p>

    <p v-if="settled && isCorrect" class="text-sm font-semibold text-success">¡Eso es! Nice ear.</p>
  </div>
</template>
