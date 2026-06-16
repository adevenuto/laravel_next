<script setup lang="ts">
import { computed, ref } from 'vue'
import { cn } from '@/lib/utils'
import type { ArticleAttachPayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'

const props = defineProps<{ payload: ArticleAttachPayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

// Tap-to-select interaction (see DECISIONS.md for the deviation rationale).
// Primary article is "armed" with a tap; each subsequent noun tap assigns the
// armed article to that noun. Mastery-gated — Next disabled until every noun
// has its correct article.

const armedArticle = ref<'la' | 'el' | null>(null)
const assigned = ref<Record<number, 'la' | 'el' | null>>({})
const coachShown = ref(false)
const settled = ref(false)

const total = computed(() => props.payload.nouns.length)

function armArticle(article: 'la' | 'el') {
  if (settled.value) return
  armedArticle.value = article
}

function assignNoun(index: number) {
  if (settled.value) return
  if (!armedArticle.value) {
    coachShown.value = true
    return
  }
  assigned.value = { ...assigned.value, [index]: armedArticle.value }
}

function isCorrect(index: number): boolean {
  return assigned.value[index] === props.payload.nouns[index].article
}

function isAssigned(index: number): boolean {
  return assigned.value[index] != null
}

const allCorrect = computed(() => {
  for (let i = 0; i < total.value; i++) {
    if (assigned.value[i] !== props.payload.nouns[i].article) return false
  }
  return true
})

const anyMisplaced = computed(() => {
  for (let i = 0; i < total.value; i++) {
    if (isAssigned(i) && !isCorrect(i)) return true
  }
  return false
})

function submit() {
  if (!allCorrect.value || settled.value) return
  settled.value = true
  emit('complete', { correct: true, score: 100, meta: { total: total.value } })
}
</script>

<template>
  <div class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Attach</p>
      <h2 class="text-2xl font-display font-semibold text-foreground leading-tight">
        {{ payload.prompt }}
      </h2>
      <p class="text-sm text-muted-foreground">Tap an article to arm it, then tap each noun.</p>
    </header>

    <!-- Article tokens -->
    <div class="flex justify-center gap-3">
      <button
        v-for="article in payload.articles"
        :key="article"
        type="button"
        :disabled="settled"
        :class="
          cn(
            'es h-14 min-w-16 rounded-soft border-2 px-5 text-2xl font-semibold transition-all duration-quick ease-quick',
            'hover:-translate-y-0.5 active:translate-y-0 disabled:cursor-not-allowed',
            armedArticle === article
              ? 'border-primary bg-primary/15 text-foreground shadow-lifted'
              : 'border-border bg-card text-foreground'
          )
        "
        @click="armArticle(article)"
      >
        {{ article }}
      </button>
    </div>

    <p v-if="coachShown && !armedArticle" class="coach-feedback text-sm text-center">
      Pick an article first, then tap a noun to assign it.
    </p>

    <!-- Noun targets -->
    <div class="grid gap-2 sm:grid-cols-2">
      <button
        v-for="(noun, i) in payload.nouns"
        :key="i"
        type="button"
        :disabled="settled"
        :class="
          cn(
            'flex items-center justify-between gap-2 rounded-soft border-2 px-4 py-3 transition-colors duration-quick',
            'hover:-translate-y-0.5 active:translate-y-0 disabled:cursor-not-allowed disabled:hover:translate-y-0',
            !isAssigned(i) && 'border-border bg-card',
            isAssigned(i) && isCorrect(i) && 'border-success bg-success/10',
            isAssigned(i) && !isCorrect(i) && 'border-coach bg-coach/10'
          )
        "
        @click="assignNoun(i)"
      >
        <span class="es text-lg font-semibold text-foreground/85">
          <span v-if="isAssigned(i)" class="mr-1 text-primary">{{ assigned[i] }}</span>
          {{ noun.es }}
        </span>
      </button>
    </div>

    <p v-if="anyMisplaced" class="coach-feedback text-sm">
      Not quite — every <span class="es">-ción</span> noun is feminine and takes
      <span class="es">la</span>. Re-tap to swap.
    </p>

    <button
      v-if="!settled"
      type="button"
      :disabled="!allCorrect"
      class="inline-flex h-12 w-full items-center justify-center rounded-soft bg-primary px-6 font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
      @click="submit"
    >
      Next
    </button>

    <p v-if="settled" class="text-sm font-semibold text-success">
      ¡Eso es! Every -ción is feminine.
    </p>
  </div>
</template>
