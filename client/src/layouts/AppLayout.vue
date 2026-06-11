<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue'
</script>

<template>
  <div class="min-h-screen text-foreground">
    <AppHeader />
    <main class="mx-auto w-full max-w-5xl p-6">
      <slot />
    </main>
  </div>
</template>
