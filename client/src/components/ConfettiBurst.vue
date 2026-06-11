<script setup lang="ts">
/**
 * One-shot celebratory confetti burst.
 *
 * Fires whenever a lesson finishes (see LessonPlayPage.vue). Renders ~60
 * CSS-animated pieces that fall + sway + tumble for ~3.5s, then auto-cleans
 * up and emits `done`. Sobremesa palette — saffron primary, jade accent,
 * cilantro success, clay coral coach.
 *
 * For the bigger unit-completion moment we layer this with the radial
 * `UnitRewardBurst` on top, giving lessons a smaller confetti rain and units
 * a visually distinct sunburst-and-sparks treatment.
 *
 * Respects `prefers-reduced-motion: reduce`: skips rendering and emits done
 * immediately so the parent state still advances.
 */
import { nextTick, onUnmounted, ref, watch } from 'vue'

interface Props {
  active: boolean
  count?: number
  duration?: number
}

const props = withDefaults(defineProps<Props>(), {
  count: 60,
  duration: 3500,
})

const emit = defineEmits<{ done: [] }>()

interface Piece {
  id: number
  left: string
  width: string
  height: string
  background: string
  borderRadius: string
  animationDuration: string
  animationDelay: string
}

const pieces = ref<Piece[]>([])
let cleanupTimer: ReturnType<typeof setTimeout> | null = null

const PALETTE = [
  // 40% saffron
  'hsl(var(--primary))',
  'hsl(var(--primary))',
  'hsl(var(--primary))',
  'hsl(var(--primary))',
  // 25% cilantro
  'hsl(var(--success))',
  'hsl(var(--success))',
  'hsl(var(--success) / 0.85)',
  // 20% jade
  'hsl(var(--accent))',
  'hsl(var(--accent) / 0.85)',
  // 15% clay
  'hsl(var(--coach))',
]

function prefersReducedMotion(): boolean {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
    return false
  }
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function buildPieces(count: number): Piece[] {
  const out: Piece[] = []
  for (let i = 0; i < count; i++) {
    const baseSize = 5 + Math.random() * 8
    const isSlim = Math.random() < 0.3
    const width = baseSize
    const height = isSlim ? baseSize * 1.9 : baseSize
    const isCircle = Math.random() < 0.6

    out.push({
      id: i,
      left: Math.random() * 100 + '%',
      width: width.toFixed(2) + 'px',
      height: height.toFixed(2) + 'px',
      background: PALETTE[Math.floor(Math.random() * PALETTE.length)],
      borderRadius: isCircle ? '50%' : '2px',
      animationDuration: (3 + Math.random() * 3).toFixed(2) + 's',
      animationDelay: (Math.random() * 1.5).toFixed(2) + 's',
    })
  }
  return out
}

function startBurst() {
  if (prefersReducedMotion()) {
    pieces.value = []
    nextTick(() => emit('done'))
    return
  }

  pieces.value = buildPieces(props.count)

  if (cleanupTimer) clearTimeout(cleanupTimer)
  cleanupTimer = setTimeout(() => {
    pieces.value = []
    cleanupTimer = null
    emit('done')
  }, props.duration)
}

watch(
  () => props.active,
  (active) => {
    if (active) startBurst()
  },
  { immediate: true }
)

onUnmounted(() => {
  if (cleanupTimer) clearTimeout(cleanupTimer)
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="pieces.length > 0"
      class="confetti-burst-root"
      aria-hidden="true"
      data-testid="confetti-burst"
    >
      <span
        v-for="p in pieces"
        :key="p.id"
        class="confetti-piece"
        :style="{
          left: p.left,
          width: p.width,
          height: p.height,
          background: p.background,
          borderRadius: p.borderRadius,
          animationDuration: p.animationDuration,
          animationDelay: p.animationDelay,
        }"
      />
    </div>
  </Teleport>
</template>

<style scoped>
.confetti-burst-root {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 50;
  overflow: hidden;
}

.confetti-piece {
  position: absolute;
  top: -24px;
  animation-name: fall-sway;
  animation-timing-function: linear;
  animation-iteration-count: 1;
  animation-fill-mode: forwards;
  will-change: transform, opacity;
}

@keyframes fall-sway {
  0% {
    transform: translate(0, 0) rotateZ(0deg) rotateY(0deg);
    opacity: 1;
  }
  25% {
    transform: translate(16px, 28vh) rotateZ(90deg) rotateY(180deg);
  }
  50% {
    transform: translate(-16px, 56vh) rotateZ(180deg) rotateY(360deg);
  }
  75% {
    transform: translate(16px, 84vh) rotateZ(270deg) rotateY(540deg);
    opacity: 0.95;
  }
  100% {
    transform: translate(0, 112vh) rotateZ(360deg) rotateY(720deg);
    opacity: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .confetti-piece {
    animation: none;
    display: none;
  }
}
</style>
