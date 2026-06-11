<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const currentYear = computed(() => new Date().getFullYear())
</script>

<template>
  <footer class="border-t border-border bg-card/40">
    <div class="mx-auto w-full max-w-5xl px-5 py-10 sm:py-12 space-y-8">
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-8">
        <div class="space-y-2 max-w-sm">
          <div class="flex items-baseline gap-1">
            <span class="font-display text-lg font-semibold tracking-tight text-foreground">
              Patrón
            </span>
            <span class="font-display text-lg italic font-semibold text-primary">Spanish</span>
          </div>
          <p class="text-sm text-muted-foreground leading-relaxed">
            A pattern-based Spanish course. Learn the rule once, produce hundreds of words.
          </p>
        </div>

        <nav class="flex flex-col gap-2">
          <p class="text-xs uppercase tracking-wider text-muted-foreground font-semibold mb-1">
            Get going
          </p>
          <RouterLink
            :to="{ name: 'signup' }"
            class="text-sm font-semibold text-foreground hover:text-primary transition-colors duration-quick"
          >
            Start learning
          </RouterLink>
          <RouterLink
            :to="{ name: 'login' }"
            class="text-sm font-semibold text-muted-foreground hover:text-foreground transition-colors duration-quick"
          >
            Log in
          </RouterLink>
        </nav>
      </div>

      <div class="border-t border-border/70 pt-6 text-center text-xs text-muted-foreground">
        &copy; {{ currentYear }} Patrón Spanish &middot; Level 1: Survival &amp; Sound System
      </div>
    </div>
  </footer>
</template>
