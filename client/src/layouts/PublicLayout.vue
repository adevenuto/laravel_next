<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue'
</script>

<template>
  <div class="min-h-screen text-foreground">
    <AppHeader />
    <slot />
  </div>
</template>
