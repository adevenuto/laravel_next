<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue'
</script>

<template>
  <div class="flex flex-col min-h-screen text-foreground">
    <AppHeader />
    <main class="flex flex-1 items-center justify-center p-4">
      <div class="w-full max-w-md">
        <slot />
      </div>
    </main>
  </div>
</template>
