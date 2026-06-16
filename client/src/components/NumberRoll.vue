<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { gsap } from 'gsap'

const props = withDefaults(
  defineProps<{
    from: number
    to: number
    durationMs?: number
    format?: (n: number) => string
  }>(),
  {
    durationMs: 1800,
    format: (n: number) => Math.round(n).toLocaleString('en-US'),
  }
)

const prefersReducedMotion =
  typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

// Initial render value: snap to `to` when reduced motion, otherwise start at `from`
// so the GSAP tween has somewhere to travel from.
const display = ref(prefersReducedMotion ? props.to : props.from)
let tween: ReturnType<typeof gsap.to> | null = null

function play(from: number, to: number) {
  tween?.kill()
  if (prefersReducedMotion) {
    display.value = to
    return
  }
  const obj = { count: from }
  tween = gsap.to(obj, {
    count: to,
    duration: props.durationMs / 1000,
    ease: 'power2.out',
    onUpdate() {
      display.value = obj.count
    },
  })
}

onMounted(() => {
  if (!prefersReducedMotion) play(props.from, props.to)
})
watch(
  () => props.to,
  (next, prev) => play(prev ?? props.from, next)
)
onUnmounted(() => tween?.kill())
</script>

<template>
  <span class="numeric-display tabular-nums">{{ format(display) }}</span>
</template>
