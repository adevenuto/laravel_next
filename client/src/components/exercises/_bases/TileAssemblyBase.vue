<script setup lang="ts">
import { computed, ref } from 'vue'
import { useDragAndDrop } from '@formkit/drag-and-drop/vue'
import { cn } from '@/lib/utils'
import type { ExerciseResult } from '@/types/domain'

interface Tile {
  id: string
  label: string
}

interface Props {
  prompt: string
  tiles: Tile[]
  expectedOrder: string[]
  groupKey?: string
}

const props = withDefaults(defineProps<Props>(), { groupKey: 'tiles' })
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

// Pool starts with all tiles; answer row starts empty.
const [poolRef, poolItems] = useDragAndDrop<Tile>([...props.tiles], { group: props.groupKey })
const [answerRef, answerItems] = useDragAndDrop<Tile>([], { group: props.groupKey })

const settled = ref(false)
const wasCorrect = ref(false)
const canSubmit = computed(
  () => !settled.value && answerItems.value.length === props.expectedOrder.length
)

function submit() {
  if (!canSubmit.value) return
  const got = answerItems.value.map((t) => t.id)
  const correct = got.every((id, i) => id === props.expectedOrder[i])
  settled.value = true
  wasCorrect.value = correct
  emit('complete', {
    correct,
    score: correct ? 100 : 0,
    meta: { submitted_order: got },
  })
}
</script>

<template>
  <div class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">Assemble</p>
      <h2 class="text-2xl font-display font-semibold text-foreground leading-tight">
        {{ prompt }}
      </h2>
    </header>

    <!-- Answer row -->
    <div class="space-y-2">
      <p class="text-xs text-muted-foreground uppercase tracking-wider">Your answer</p>
      <div
        ref="answerRef"
        :class="
          cn(
            'flex min-h-[4rem] flex-wrap gap-2 rounded-soft border-2 border-dashed border-border bg-card/60 p-3 transition-all duration-quick ease-quick',
            settled && wasCorrect && 'border-success bg-success/5',
            settled && !wasCorrect && 'border-coach bg-coach/5'
          )
        "
      >
        <span
          v-for="t in answerItems"
          :key="t.id"
          class="es cursor-grab select-none rounded-soft border-2 border-border bg-card px-4 py-2 text-lg font-semibold shadow-raised"
        >
          {{ t.label }}
        </span>
        <span
          v-if="answerItems.length === 0"
          class="self-center px-2 text-sm italic text-muted-foreground"
        >
          Drag tiles here in order…
        </span>
      </div>
    </div>

    <!-- Tile pool -->
    <div class="space-y-2">
      <p class="text-xs text-muted-foreground uppercase tracking-wider">Available tiles</p>
      <div
        ref="poolRef"
        class="flex min-h-[4rem] flex-wrap gap-2 rounded-soft border-2 border-border bg-muted/40 p-3"
      >
        <span
          v-for="t in poolItems"
          :key="t.id"
          class="es cursor-grab select-none rounded-soft border-2 border-border bg-card px-4 py-2 text-lg font-semibold shadow-raised hover:-translate-y-0.5 transition-transform duration-quick ease-quick"
        >
          {{ t.label }}
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

    <p v-if="settled && wasCorrect" class="text-sm font-semibold text-success">¡Eso es!</p>
    <p v-else-if="settled && !wasCorrect" class="coach-feedback text-sm">
      Not quite — review the order and try again next time.
    </p>
  </div>
</template>
