<script setup lang="ts">
import { computed, inject, onMounted, ref, watch } from 'vue'
import MultipleChoiceBase from './_bases/MultipleChoiceBase.vue'
import type { EarTrainingPayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'
import type { AudioApi } from '@/composables/audioInjection'
import { audioKey } from '@/composables/audioInjection'

const props = defineProps<{ payload: EarTrainingPayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const audio = inject<AudioApi | null>(audioKey, null)

const roundIndex = ref(0)
const correctCount = ref(0)
const total = computed(() => props.payload.rounds.length)
const round = computed(() => props.payload.rounds[roundIndex.value])

const options = computed(() => props.payload.options.map((opt) => ({ label: opt, value: opt })))

function playPrompt(key: string) {
  audio?.play(key, { es: round.value.answer })
}

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

onMounted(() => {
  // Beat between teach-screen exit and the first auto-play so audio doesn't
  // slam in the instant the exercise mounts.
  setTimeout(() => audio?.play(round.value.audio_key, { es: round.value.answer }), 450)
})

watch(roundIndex, (i) => {
  const r = props.payload.rounds[i]
  audio?.play(r.audio_key, { es: r.answer })
})
</script>

<template>
  <div class="space-y-4">
    <div class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
      Round {{ roundIndex + 1 }} of {{ total }}
    </div>

    <MultipleChoiceBase
      :key="roundIndex"
      :prompt="props.payload.prompt"
      :options="options"
      :correct-value="round.answer"
      :prompt-audio-key="round.audio_key"
      :play-prompt-audio="playPrompt"
      @complete="onRoundComplete"
    />
  </div>
</template>
