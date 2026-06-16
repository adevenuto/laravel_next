<script setup lang="ts">
import { computed, inject, nextTick, onMounted, ref, watch } from 'vue'
import { gsap } from 'gsap'
import { Volume2 } from 'lucide-vue-next'
import type { WordForgePayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'
import { audioKey, type AudioApi } from '@/composables/audioInjection'

const props = defineProps<{ payload: WordForgePayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const audio = inject<AudioApi | null>(audioKey, null)

const wordIndex = ref(0)
const typed = ref('')
const settled = ref(false)
const coachLine = ref<string | null>(null)
const coachTone = ref<'error' | 'success'>('error')
const forged = ref(false)
const transformSparkle = ref(false)
const showCloser = ref(false)
const coldGuesses = ref(0)

const inputEl = ref<HTMLInputElement | null>(null)
const cardEl = ref<HTMLElement | null>(null)

const total = computed(() => props.payload.words.length)
const word = computed(() => props.payload.words[wordIndex.value] ?? null)
const expected = computed(() => word.value?.es ?? '')

// Per-character match is accent-insensitive (matches useFuzzyMatch semantics).
// Typing `o` where the expected char is `ó` is treated as correct — the user is
// learning the transformation rule, not the keyboard. A gentle reveal on forge
// shows the proper accented form.
function stripChar(c: string): string {
  return c.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
}

function stripAll(s: string): string {
  return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
}

// Strict-prefix per-letter validation, accent-tolerant. The wrong-flag is true
// iff the typed string contains a character whose stripped form does not match
// the stripped expected char at the same index.
const wrongIndex = computed(() => {
  if (!expected.value) return -1
  for (let i = 0; i < typed.value.length; i++) {
    if (stripChar(typed.value[i]) !== stripChar(expected.value[i])) return i
  }
  return -1
})
const hasWrong = computed(() => wrongIndex.value !== -1)

// True iff the user's input matches expected when accents are stripped on both
// sides — but the expected actually has accents the user didn't type. Drives the
// "Real spelling" coach line on forge.
const accentDropped = computed(() => {
  if (!expected.value || typed.value.length !== expected.value.length) return false
  return stripAll(typed.value) === stripAll(expected.value) && typed.value !== expected.value
})

// Compute the index where the T → C transformation happens by aligning the
// English suffix back from the end of the Spanish word. For 'tion → ción',
// the C in Spanish is at expected.length - 4 (positions: c-i-ó-n). For other
// rule_keys this is a fallback that doesn't fire the sparkle.
const transformIndex = computed(() => {
  if (!expected.value) return -1
  if (props.payload.rule_key === 'tion_cion') {
    // The Spanish word always ends in 'ción' or 'ciones'. The c that replaces
    // the English t is the one preceding 'ión' (or 'iones').
    const tail = expected.value.endsWith('ciones') ? 6 : 4
    return expected.value.length - tail
  }
  return -1
})

// Letters of expected so we can render slot cards.
const slots = computed(() => {
  if (!expected.value) return []
  return expected.value.split('').map((ch, i) => ({
    expected: ch,
    typed: typed.value[i] ?? null,
    isWrong: typed.value[i] !== undefined && stripChar(typed.value[i]) !== stripChar(ch),
    isTransform: i === transformIndex.value,
  }))
})

function onInput(e: Event) {
  if (settled.value) return
  const next = (e.target as HTMLInputElement).value
  // Reject extra characters past expected length.
  typed.value = next.slice(0, expected.value.length)

  // Coach line on the first wrong char (uses tweak_note if present, else default).
  if (hasWrong.value) {
    coachLine.value =
      props.payload.words[wordIndex.value].tweak_note ??
      'Almost — that letter does not match. Backspace and try again.'
    coachTone.value = 'error'
  } else {
    coachLine.value = null
  }

  // Fire the T → C sparkle the moment the user lands the transform letter correctly.
  if (
    transformIndex.value >= 0 &&
    typed.value.length > transformIndex.value &&
    typed.value[transformIndex.value] === expected.value[transformIndex.value] &&
    !hasWrong.value &&
    !transformSparkle.value
  ) {
    transformSparkle.value = true
    setTimeout(() => (transformSparkle.value = false), 700)
  }

  // Completion check — accent-insensitive.
  if (stripAll(typed.value) === stripAll(expected.value)) {
    onForge()
  }
}

const prefersReducedMotion =
  typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

function onForge() {
  if (!word.value) return
  settled.value = true
  forged.value = true
  if (props.payload.is_guess_mode) coldGuesses.value++
  // Reveal the proper accented spelling in the input + slots so the user sees
  // the real word, not their accent-less version.
  if (accentDropped.value) {
    coachLine.value = `Real spelling: ${expected.value}`
    coachTone.value = 'success'
    typed.value = expected.value
  } else {
    coachLine.value = null
  }
  audio?.play(word.value.audio_key, { es: expected.value })

  if (cardEl.value && !prefersReducedMotion) {
    gsap.fromTo(
      cardEl.value,
      { scale: 1 },
      {
        scale: 1.015,
        duration: 0.32,
        yoyo: true,
        repeat: 1,
        ease: 'power2.out',
      }
    )
  }
}

function nextWord() {
  if (wordIndex.value >= total.value - 1) {
    finish()
    return
  }
  wordIndex.value++
  typed.value = ''
  coachLine.value = null
  coachTone.value = 'error'
  forged.value = false
  settled.value = false
  transformSparkle.value = false
  nextTick(() => inputEl.value?.focus())
}

function finish() {
  if (props.payload.is_guess_mode && props.payload.closer_message) {
    showCloser.value = true
    return
  }
  emitComplete()
}

function emitComplete() {
  const score = 100
  const meta: Record<string, unknown> = {
    words_forged: total.value,
  }
  if (props.payload.is_guess_mode) {
    const perCorrect = props.payload.vocab_counter_per_correct ?? 1
    meta.vocab_delta = coldGuesses.value * perCorrect
    meta.cold_guesses = coldGuesses.value
  }
  emit('complete', { correct: true, score, meta })
}

const closerText = computed(() => {
  if (!props.payload.closer_message) return ''
  return props.payload.closer_message.replace('{count}', String(coldGuesses.value))
})

function replayPrompt() {
  if (!word.value) return
  audio?.play(word.value.audio_key, { es: expected.value })
}

onMounted(() => {
  nextTick(() => inputEl.value?.focus())
})

watch(wordIndex, () => {
  nextTick(() => inputEl.value?.focus())
})
</script>

<template>
  <div v-if="!showCloser && word" class="space-y-4">
    <div
      class="flex items-center justify-between text-xs uppercase tracking-wider text-muted-foreground font-semibold"
    >
      <span>Word {{ wordIndex + 1 }} of {{ total }}</span>
      <span>{{ payload.transformation_hint }}</span>
    </div>

    <header class="space-y-2">
      <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Forge</p>
      <h2 class="text-2xl font-display font-semibold text-foreground leading-tight">
        {{ payload.prompt }}
      </h2>
      <p class="text-3xl font-display font-semibold text-foreground/85">
        {{ word.en }}
      </p>
    </header>

    <div ref="cardEl" class="sunlit-card p-6 space-y-4" :class="{ 'border-success/40': forged }">
      <!-- Slot row: each letter is a card showing the expected glyph, colored by typed state. -->
      <div class="flex flex-wrap justify-center gap-1.5">
        <span
          v-for="(slot, i) in slots"
          :key="i"
          :class="[
            'es inline-flex h-12 min-w-10 items-center justify-center rounded-soft border-2 px-2 text-xl font-semibold transition-colors duration-quick',
            slot.typed === null && 'border-border bg-card text-foreground/40',
            slot.typed !== null && !slot.isWrong && 'border-success bg-success/15 text-foreground',
            slot.typed !== null && slot.isWrong && 'border-coach bg-coach/15 text-foreground',
            slot.isTransform && transformSparkle && 'animate-correct-pop ring-2 ring-primary',
          ]"
        >
          {{ slot.typed ?? slot.expected }}
        </span>
      </div>

      <input
        ref="inputEl"
        :value="typed"
        type="text"
        autocomplete="off"
        autocapitalize="off"
        autocorrect="off"
        spellcheck="false"
        class="es w-full rounded-soft border-2 border-border bg-card px-4 py-3 text-xl text-center font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
        :placeholder="`Type the Spanish for ${word.en}`"
        :disabled="settled"
        @input="onInput"
      />

      <p
        v-if="coachLine"
        :class="
          coachTone === 'success'
            ? 'rounded-soft border-2 border-success/40 bg-success/10 px-4 py-2 text-sm font-semibold text-success'
            : 'coach-feedback text-sm'
        "
      >
        {{ coachLine }}
      </p>

      <div v-if="forged" class="flex items-center justify-between gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-pill bg-secondary px-4 py-2 text-secondary-foreground font-semibold text-sm transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0"
          @click="replayPrompt"
        >
          <Volume2 class="h-4 w-4" />
          Hear it
        </button>
        <button
          type="button"
          class="inline-flex h-12 flex-1 items-center justify-center rounded-soft bg-primary px-6 font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          @click="nextWord"
        >
          {{ wordIndex >= total - 1 ? 'Finish' : 'Next word →' }}
        </button>
      </div>
    </div>
  </div>

  <div v-else class="space-y-6 text-center sunlit-card p-8">
    <p class="numeric-display text-6xl text-primary">+{{ coldGuesses }}</p>
    <p class="font-display text-2xl text-foreground leading-tight">{{ closerText }}</p>
    <button
      type="button"
      class="inline-flex h-12 w-full items-center justify-center rounded-soft bg-primary px-6 font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0"
      @click="emitComplete"
    >
      Continue
    </button>
  </div>
</template>
