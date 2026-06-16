<script setup lang="ts">
import { computed, inject, ref, watch } from 'vue'
import TapTargetBase from './_bases/TapTargetBase.vue'
import type { AccentPlacerPayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'
import { audioKey, type AudioApi } from '@/composables/audioInjection'

const props = defineProps<{ payload: AccentPlacerPayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const audio = inject<AudioApi | null>(audioKey, null)

const wordIndex = ref(0)
const correctCount = ref(0)
const total = computed(() => props.payload.words.length)
const word = computed(() => props.payload.words[wordIndex.value])

// Render each letter of the base form as a tap target. The vowel at
// accent_letter_index is the only correct one. Once tapped correctly, the
// display swaps to the accented form.
const targets = computed(() =>
  word.value.base.split('').map((char, i) => ({
    id: `${wordIndex.value}-${i}`,
    label: char,
    isAnswer: i === word.value.accent_letter_index,
  }))
)

const solved = ref(false)
const displayWord = computed(() => (solved.value ? word.value.accented : word.value.base))

function onWordComplete(result: ExerciseResult) {
  if (result.correct) {
    correctCount.value++
    solved.value = true
    audio?.play(`tts_cognate_${slug(word.value.accented)}`, { es: word.value.accented })
  }

  // Brief pause so the user sees the swapped-to-accented form, then advance.
  setTimeout(() => {
    if (wordIndex.value >= total.value - 1) {
      const score = Math.round((correctCount.value / total.value) * 100)
      emit('complete', {
        correct: correctCount.value === total.value,
        score,
        meta: { correct: correctCount.value, total: total.value },
      })
      return
    }
    wordIndex.value++
    solved.value = false
  }, 900)
}

watch(wordIndex, () => {
  solved.value = false
})

function slug(es: string): string {
  return es
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-zñ]+/g, '_')
    .replace(/^_|_$/g, '')
}
</script>

<template>
  <div class="space-y-4">
    <div class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
      Word {{ wordIndex + 1 }} of {{ total }} ·
      <span class="es text-foreground">{{ displayWord }}</span>
    </div>

    <TapTargetBase
      :key="wordIndex"
      :prompt="props.payload.prompt"
      :targets="targets"
      :multiple="false"
      coaching="Not that vowel — the stress lives somewhere else. Try again."
      @complete="onWordComplete"
    />
  </div>
</template>
