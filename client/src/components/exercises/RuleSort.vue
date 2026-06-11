<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useDragAndDrop } from '@formkit/drag-and-drop/vue'
import { cn } from '@/lib/utils'
import type { RuleSortPayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'

interface Word {
  id: string
  label: string
  bucket: string
}

const props = defineProps<{ payload: RuleSortPayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

// Phase 2 supports up to 3 buckets (the Unit 1.1 RuleSort has exactly 3 —
// Rule 1, Rule 2, Rule 3). If a future payload needs more, this is the spot.
const MAX_BUCKETS = 3

const settled = ref(false)
const score = ref(0)
const wasCorrect = ref(false)

const initialWords = computed<Word[]>(() =>
  props.payload.words.map((w, i) => ({
    id: `w-${i}`,
    label: w.es,
    bucket: w.bucket,
  }))
)

const bucketDefs = computed(() => props.payload.buckets.slice(0, MAX_BUCKETS))

const group = 'rulesort'
const [poolRef, poolWords] = useDragAndDrop<Word>([...initialWords.value], { group })
const [b0Ref, b0Words] = useDragAndDrop<Word>([], { group })
const [b1Ref, b1Words] = useDragAndDrop<Word>([], { group })
const [b2Ref, b2Words] = useDragAndDrop<Word>([], { group })

const bucketRefs = [b0Ref, b1Ref, b2Ref]
const bucketLists = [b0Words, b1Words, b2Words]

const canSubmit = computed(() => {
  if (settled.value) return false
  return poolWords.value.length === 0
})

function submit() {
  if (!canSubmit.value) return
  const total = initialWords.value.length
  let hits = 0

  bucketDefs.value.forEach((bucket, i) => {
    for (const word of bucketLists[i].value) {
      if (word.bucket === bucket.key) hits++
    }
  })

  score.value = Math.round((hits / total) * 100)
  wasCorrect.value = hits === total
  settled.value = true
  emit('complete', {
    correct: wasCorrect.value,
    score: score.value,
    meta: { hits, total },
  })
}

onMounted(() => {
  // Defensive: guard against a malformed payload with more buckets than supported.
  if (props.payload.buckets.length > MAX_BUCKETS && import.meta.env.DEV) {
    // eslint-disable-next-line no-console
    console.warn(
      `[RuleSort] payload has ${props.payload.buckets.length} buckets; Phase 2 only renders the first ${MAX_BUCKETS}.`
    )
  }
})

function isCorrectPlacement(word: Word, bucketKey: string): boolean {
  return word.bucket === bucketKey
}
</script>

<template>
  <div class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Sort</p>
      <h2 class="text-2xl font-display font-semibold text-foreground leading-tight">
        {{ props.payload.prompt }}
      </h2>
    </header>

    <!-- Buckets -->
    <div class="grid gap-3 md:grid-cols-3">
      <div v-for="(bucket, i) in bucketDefs" :key="bucket.key" class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ bucket.label }}
        </p>
        <div
          :ref="
            (el) => {
              if (el) bucketRefs[i].value = el as HTMLElement
            }
          "
          :class="
            cn(
              'flex min-h-[7rem] flex-col gap-2 rounded-soft border-2 border-dashed border-border bg-card/60 p-3 transition-all duration-quick ease-quick',
              settled && 'bg-card/40'
            )
          "
        >
          <span
            v-for="word in bucketLists[i].value"
            :key="word.id"
            :class="
              cn(
                'es cursor-grab select-none rounded-soft border-2 px-3 py-2 text-base font-semibold shadow-raised text-left',
                settled && isCorrectPlacement(word, bucket.key) && 'border-success bg-success/10',
                settled && !isCorrectPlacement(word, bucket.key) && 'border-coach bg-coach/10',
                !settled && 'border-border bg-card'
              )
            "
          >
            {{ word.label }}
          </span>
          <span
            v-if="bucketLists[i].value.length === 0"
            class="self-center px-2 py-2 text-xs italic text-muted-foreground"
          >
            Drop words here…
          </span>
        </div>
      </div>
    </div>

    <!-- Pool -->
    <div class="space-y-2">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
        Drag from here
      </p>
      <div
        ref="poolRef"
        class="flex min-h-[5rem] flex-wrap gap-2 rounded-soft border-2 border-border bg-muted/40 p-3"
      >
        <span
          v-for="word in poolWords"
          :key="word.id"
          class="es cursor-grab select-none rounded-soft border-2 border-border bg-card px-4 py-2 text-lg font-semibold shadow-raised hover:-translate-y-0.5 transition-transform duration-quick ease-quick"
        >
          {{ word.label }}
        </span>
        <span
          v-if="poolWords.length === 0 && !settled"
          class="self-center px-2 text-sm italic text-muted-foreground"
        >
          All words placed. Check your answer.
        </span>
      </div>
    </div>

    <button
      v-if="!settled"
      type="button"
      :disabled="!canSubmit"
      class="inline-flex h-12 w-full items-center justify-center rounded-soft bg-primary px-6 font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
      @click="submit"
    >
      Check
    </button>

    <p v-if="settled && wasCorrect" class="text-sm font-semibold text-success">
      All sorted! {{ score }}/100.
    </p>
    <p v-else-if="settled && !wasCorrect" class="coach-feedback text-sm">
      {{ score }}/100 — review which words came back to the wrong rule.
    </p>
  </div>
</template>
