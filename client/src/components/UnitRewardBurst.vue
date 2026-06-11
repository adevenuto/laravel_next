<script setup lang="ts">
/**
 * Distinct from ConfettiBurst — this is the bigger moment when a whole UNIT
 * is completed. A radial halo blooms from center, a hard ring snaps outward
 * over it, and ~24 sparks shoot in evenly-spaced directions like a sunburst.
 *
 * Renders on top of the confetti (z-index 60 vs 50) so when both fire at the
 * same time (unit-completing lesson) the sunburst leads the celebration and
 * the confetti fills the periphery.
 *
 * `prefers-reduced-motion: reduce` short-circuits to a no-render + immediate
 * `done` so parent state still advances.
 */
import { nextTick, onUnmounted, ref, watch } from 'vue'

interface Props {
  active: boolean
  duration?: number
}

const props = withDefaults(defineProps<Props>(), {
  duration: 2400,
})

const emit = defineEmits<{ done: [] }>()

interface Spark {
  id: number
  angle: number
  distance: number
  size: number
  background: string
  delay: string
  duration: string
}

const SPARK_PALETTE = [
  'hsl(var(--primary))',
  'hsl(var(--primary))',
  'hsl(var(--primary))',
  'hsl(var(--success))',
  'hsl(var(--accent))',
  'hsl(var(--coach))',
]

const visible = ref(false)
const sparks = ref<Spark[]>([])
let cleanupTimer: ReturnType<typeof setTimeout> | null = null

function prefersReducedMotion(): boolean {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
    return false
  }
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function buildSparks(): Spark[] {
  const count = 24
  const out: Spark[] = []
  for (let i = 0; i < count; i++) {
    // Evenly distributed angles with a touch of jitter so it doesn't look like
    // a clock face. Some sparks travel farther for depth.
    const baseAngle = (i / count) * 360
    const jitter = Math.random() * 10 - 5
    const angle = baseAngle + jitter
    const distance = 220 + Math.random() * 180
    const size = 6 + Math.random() * 8
    out.push({
      id: i,
      angle,
      distance,
      size,
      background: SPARK_PALETTE[Math.floor(Math.random() * SPARK_PALETTE.length)],
      delay: (Math.random() * 0.15).toFixed(2) + 's',
      duration: (1.3 + Math.random() * 0.5).toFixed(2) + 's',
    })
  }
  return out
}

function fire() {
  if (prefersReducedMotion()) {
    visible.value = false
    sparks.value = []
    nextTick(() => emit('done'))
    return
  }

  sparks.value = buildSparks()
  visible.value = true

  if (cleanupTimer) clearTimeout(cleanupTimer)
  cleanupTimer = setTimeout(() => {
    visible.value = false
    sparks.value = []
    cleanupTimer = null
    emit('done')
  }, props.duration)
}

watch(
  () => props.active,
  (active) => {
    if (active) fire()
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
      v-if="visible"
      class="reward-burst-root"
      aria-hidden="true"
      data-testid="unit-reward-burst"
    >
      <div class="reward-halo" />
      <div class="reward-ring" />
      <span
        v-for="s in sparks"
        :key="s.id"
        class="reward-spark"
        :style="{
          '--angle': s.angle + 'deg',
          '--distance': s.distance + 'px',
          width: s.size + 'px',
          height: s.size + 'px',
          background: s.background,
          animationDelay: s.delay,
          animationDuration: s.duration,
        }"
      />
    </div>
  </Teleport>
</template>

<style scoped>
.reward-burst-root {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 60;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.reward-halo {
  position: absolute;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: radial-gradient(circle, hsl(var(--primary) / 0.55) 0%, hsl(var(--primary) / 0) 65%);
  animation: halo-expand 2.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
  will-change: transform, opacity;
}

@keyframes halo-expand {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  15% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(6);
    opacity: 0;
  }
}

.reward-ring {
  position: absolute;
  width: 140px;
  height: 140px;
  border-radius: 50%;
  border: 3px solid hsl(var(--primary));
  box-shadow:
    0 0 24px hsl(var(--primary) / 0.6),
    inset 0 0 18px hsl(var(--primary) / 0.35);
  animation: ring-snap 1.7s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  will-change: transform, opacity;
}

@keyframes ring-snap {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  35% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(3.5);
    opacity: 0;
  }
}

.reward-spark {
  position: absolute;
  border-radius: 50%;
  box-shadow: 0 0 8px currentColor;
  animation-name: spark-radiate;
  animation-iteration-count: 1;
  animation-fill-mode: forwards;
  animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
  will-change: transform, opacity;
}

@keyframes spark-radiate {
  0% {
    transform: rotate(var(--angle)) translateX(0) scale(0);
    opacity: 0;
  }
  12% {
    transform: rotate(var(--angle)) translateX(40px) scale(1);
    opacity: 1;
  }
  100% {
    transform: rotate(var(--angle)) translateX(var(--distance)) scale(0.3);
    opacity: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .reward-halo,
  .reward-ring,
  .reward-spark {
    animation: none;
    display: none;
  }
}
</style>
