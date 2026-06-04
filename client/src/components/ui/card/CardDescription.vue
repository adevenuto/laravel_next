<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()
const cls = computed(() => cn('text-sm text-muted-foreground', props.class))
</script>

<template>
  <p :class="cls">
    <slot />
  </p>
</template>
