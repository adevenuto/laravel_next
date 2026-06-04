<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()
const cls = computed(() => cn('p-6 pt-0', props.class))
</script>

<template>
  <div :class="cls">
    <slot />
  </div>
</template>
