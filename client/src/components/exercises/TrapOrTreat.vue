<script setup lang="ts">
import { computed, ref } from 'vue'
import MultipleChoiceBase from './_bases/MultipleChoiceBase.vue'
import type { TrapOrTreatPayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'

const props = defineProps<{ payload: TrapOrTreatPayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const roundIndex = ref(0)
const correctCount = ref(0)
const rebelsCaptured = ref<string[]>([])
const lastCapturedRebel = ref<string | null>(null)

const total = computed(() => props.payload.rounds.length)
const round = computed(() => props.payload.rounds[roundIndex.value])

// Each round renders two cards: naïve form vs real form. Value === the es string.
const options = computed(() => [
  { label: round.value.naive_es, value: round.value.naive_es },
  { label: round.value.real_es, value: round.value.real_es },
])

const correctValue = computed(() => round.value.real_es)

// Per-option coach: a wrong tap (the naïve form) shows the round's explanation
// so the user sees WHY their guess wasn't the real word.
const coachingPerValue = computed<Record<string, string>>(() => ({
  [round.value.naive_es]: round.value.explanation,
}))

const promptWithEn = computed(() => `${props.payload.prompt} (${round.value.en})`)

function onRoundComplete(result: ExerciseResult) {
  if (result.correct) {
    correctCount.value++
    // Capture the rebel if this round was an exception.
    if (round.value.is_exception && round.value.rebel_word_key) {
      if (!rebelsCaptured.value.includes(round.value.rebel_word_key)) {
        rebelsCaptured.value.push(round.value.rebel_word_key)
      }
      lastCapturedRebel.value = round.value.real_es
    } else {
      lastCapturedRebel.value = null
    }
  }

  // Brief pause so the "Rebel captured" toast (if any) registers, then advance.
  setTimeout(
    () => {
      if (roundIndex.value >= total.value - 1) {
        const score = Math.round((correctCount.value / total.value) * 100)
        emit('complete', {
          correct: correctCount.value === total.value,
          score,
          meta: {
            correct: correctCount.value,
            total: total.value,
            rebels_captured: rebelsCaptured.value,
          },
        })
        return
      }
      roundIndex.value++
      lastCapturedRebel.value = null
    },
    lastCapturedRebel.value ? 1300 : 600
  )
}
</script>

<template>
  <div class="space-y-4">
    <div class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
      Round {{ roundIndex + 1 }} of {{ total }}
    </div>

    <MultipleChoiceBase
      :key="roundIndex"
      :prompt="promptWithEn"
      :options="options"
      :correct-value="correctValue"
      :coaching-per-value="coachingPerValue"
      :require-perfect="true"
      @complete="onRoundComplete"
    />

    <p
      v-if="lastCapturedRebel"
      class="rounded-soft border-2 border-primary/40 bg-primary/5 px-4 py-3 text-sm font-semibold text-primary"
    >
      ⚡ Rebel captured: <span class="es">{{ lastCapturedRebel }}</span>
    </p>
  </div>
</template>
