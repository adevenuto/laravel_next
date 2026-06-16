<script setup lang="ts">
import { computed, inject, onMounted, ref, watch } from 'vue'
import MultipleChoiceBase from './_bases/MultipleChoiceBase.vue'
import type { ChooseTheRealPayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'
import { audioKey, type AudioApi } from '@/composables/audioInjection'

const props = defineProps<{ payload: ChooseTheRealPayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const audio = inject<AudioApi | null>(audioKey, null)

const roundIndex = ref(0)
const correctCount = ref(0)
const total = computed(() => props.payload.rounds.length)
const round = computed(() => props.payload.rounds[roundIndex.value])

// Each round's options become the MultipleChoiceBase options. Value = the option
// string itself, so the coachingPerValue map keys directly on the wrong forms.
const options = computed(() => round.value.options.map((opt) => ({ label: opt, value: opt })))

const correctValue = computed(() => round.value.options[round.value.answer_index])

const coachingPerValue = computed<Record<string, string>>(() => {
  const map: Record<string, string> = {}
  round.value.options.forEach((opt, i) => {
    const why = round.value.why_wrong[i]
    if (why) map[opt] = why
  })
  return map
})

const promptWithEn = computed(() => `${props.payload.prompt} (${round.value.en})`)

function onRoundComplete(result: ExerciseResult) {
  if (result.correct) correctCount.value++
  if (roundIndex.value >= total.value - 1) {
    const score = Math.round((correctCount.value / total.value) * 100)
    emit('complete', {
      correct: correctCount.value === total.value,
      score,
      meta: { correct: correctCount.value, total: total.value },
    })
    return
  }
  roundIndex.value++
}

function playReal() {
  audio?.play(`tts_cognate_${slug(correctValue.value)}`, { es: correctValue.value })
}

function slug(es: string): string {
  return es
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-zñ]+/g, '_')
    .replace(/^_|_$/g, '')
}

onMounted(() => setTimeout(() => playReal(), 450))
watch(roundIndex, () => playReal())
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
  </div>
</template>
