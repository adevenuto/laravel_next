<script setup lang="ts">
import { computed, inject, onMounted, ref, watch } from 'vue'
import { Volume2 } from 'lucide-vue-next'
import TapTargetBase from './_bases/TapTargetBase.vue'
import type { SilentLetterTapPayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'
import { audioKey, type AudioApi } from '@/composables/audioInjection'

const props = defineProps<{ payload: SilentLetterTapPayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const audio = inject<AudioApi | null>(audioKey, null)

const wordIndex = ref(0)
const accumScore = ref(0)
const total = computed(() => props.payload.words.length)
const word = computed(() => props.payload.words[wordIndex.value])

const targets = computed(() =>
  word.value.es.split('').map((char, i) => ({
    id: `${wordIndex.value}-${i}`,
    label: char,
    isAnswer: word.value.silent_indices.includes(i),
  }))
)

function replayPrompt() {
  audio?.play(word.value.audio_key, { es: word.value.es })
}

function onWordComplete(result: ExerciseResult) {
  accumScore.value += result.score

  if (wordIndex.value >= total.value - 1) {
    const avg = Math.round(accumScore.value / total.value)
    emit('complete', {
      correct: avg === 100,
      score: avg,
      meta: { words: total.value, avg },
    })
    return
  }
  wordIndex.value++
}

onMounted(() => setTimeout(() => audio?.play(word.value.audio_key, { es: word.value.es }), 450))
watch(wordIndex, (i) => {
  const w = props.payload.words[i]
  audio?.play(w.audio_key, { es: w.es })
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
        Word {{ wordIndex + 1 }} of {{ total }}
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-pill bg-muted px-3 py-1.5 text-xs font-semibold text-muted-foreground hover:bg-secondary hover:text-secondary-foreground transition-colors duration-quick"
        @click="replayPrompt"
      >
        <Volume2 class="h-3.5 w-3.5" />
        Hear it
      </button>
    </div>

    <TapTargetBase
      :key="wordIndex"
      :prompt="props.payload.prompt"
      :targets="targets"
      :multiple="true"
      submit-label="Next"
      @complete="onWordComplete"
    />
  </div>
</template>
