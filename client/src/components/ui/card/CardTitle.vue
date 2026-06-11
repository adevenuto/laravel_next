<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()
const cls = computed(() =>
  cn('font-display text-2xl font-semibold leading-none tracking-tight', props.class)
)
</script>

<template>
  <h3 :class="cls">
    <slot />
  </h3>
</template>
