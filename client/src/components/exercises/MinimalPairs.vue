<script setup lang="ts">
import { computed, inject, onMounted, ref, watch } from 'vue'
import MultipleChoiceBase from './_bases/MultipleChoiceBase.vue'
import type { MinimalPairsPayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'
import { audioKey, type AudioApi } from '@/composables/audioInjection'

const props = defineProps<{ payload: MinimalPairsPayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const audio = inject<AudioApi | null>(audioKey, null)

const roundIndex = ref(0)
const correctCount = ref(0)
const total = computed(() => props.payload.pairs.length)
const pair = computed(() => props.payload.pairs[roundIndex.value])

const options = computed(() => [
  {
    label: pair.value.a.es,
    value: 'a' as const,
    sublabel: pair.value.a.gloss,
    audioKey: pair.value.a.audio_key,
  },
  {
    label: pair.value.b.es,
    value: 'b' as const,
    sublabel: pair.value.b.gloss,
    audioKey: pair.value.b.audio_key,
  },
])

function promptEs(p = pair.value): string {
  return p.answer === 'a' ? p.a.es : p.b.es
}

function playPrompt(key: string) {
  audio?.play(key, { es: promptEs() })
}

function playOption(key: string) {
  // The base passes the option's audio key. Look up the matching option to
  // recover its Spanish text for the fallback.
  const opt = pair.value.a.audio_key === key ? pair.value.a : pair.value.b
  audio?.play(key, { es: opt.es })
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

onMounted(() => setTimeout(() => audio?.play(pair.value.prompt_audio, { es: promptEs() }), 450))
watch(roundIndex, (i) => {
  const p = props.payload.pairs[i]
  audio?.play(p.prompt_audio, { es: promptEs(p) })
})
</script>

<template>
  <div class="space-y-4">
    <div class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
      Pair {{ roundIndex + 1 }} of {{ total }}
    </div>

    <MultipleChoiceBase
      :key="roundIndex"
      :prompt="props.payload.prompt"
      :options="options"
      :correct-value="pair.answer"
      :prompt-audio-key="pair.prompt_audio"
      :play-prompt-audio="playPrompt"
      :play-option-audio="playOption"
      @complete="onRoundComplete"
    />
  </div>
</template>
