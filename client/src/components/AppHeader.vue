<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Home, UserCircle } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { cn } from '@/lib/utils'

const auth = useAuthStore()

const isAuthed = computed(() => auth.isAuthenticated)

const firstName = computed(() => auth.user?.first_name ?? '')
const fullName = computed(() => {
  if (!auth.user) return ''
  return `${auth.user.first_name} ${auth.user.last_name}`.trim()
})

// Wordmark goes to /dashboard for authed users, / for guests.
const wordmarkDest = computed(() => (isAuthed.value ? { name: 'dashboard' } : { name: 'landing' }))

const navLinkClass = (isActive: boolean) =>
  cn(
    'inline-flex items-center gap-1.5 rounded-pill px-3 py-1.5 text-sm font-semibold transition-colors duration-quick ease-quick',
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background',
    isActive
      ? 'bg-primary/15 text-foreground'
      : 'text-muted-foreground hover:bg-muted hover:text-foreground'
  )
</script>

<template>
  <header class="border-b border-border/60 bg-card/80 backdrop-blur-sm">
    <div class="flex items-center justify-between w-full max-w-5xl gap-3 p-4 mx-auto">
      <div class="flex items-center min-w-0 gap-1 sm:gap-4">
        <RouterLink :to="wordmarkDest" class="flex items-baseline gap-1 group shrink-0">
          <span class="text-xl font-semibold tracking-tight font-display text-foreground">
            Patrón
          </span>
          <span
            class="font-display text-xl italic font-semibold text-primary transition-transform duration-quick ease-quick group-hover:-translate-y-0.5"
          >
            Spanish
          </span>
        </RouterLink>

        <!-- Authed-only nav: Dashboard + Profile -->
        <nav v-if="isAuthed" class="flex items-center gap-1">
          <RouterLink v-slot="{ isActive, navigate, href }" :to="{ name: 'dashboard' }" custom>
            <a :href="href" :class="navLinkClass(isActive)" @click="navigate">
              <Home class="w-4 h-4" />
              <span class="hidden sm:inline">Dashboard</span>
            </a>
          </RouterLink>
          <RouterLink v-slot="{ isActive, navigate, href }" :to="{ name: 'profile' }" custom>
            <a :href="href" :class="navLinkClass(isActive)" @click="navigate">
              <UserCircle class="w-4 h-4" />
              <span class="hidden sm:inline">Profile</span>
            </a>
          </RouterLink>
        </nav>
      </div>

      <!-- Right side: user name (links to profile where logout now lives) for authed,
           Log in + Sign up for guests. -->
      <RouterLink
        v-if="isAuthed && firstName"
        :to="{ name: 'profile' }"
        class="shrink-0 truncate text-sm font-semibold text-muted-foreground hover:text-foreground transition-colors duration-quick"
      >
        <span class="sm:hidden">{{ firstName }}</span>
        <span class="hidden sm:inline">{{ fullName }}</span>
      </RouterLink>

      <nav v-if="!isAuthed" class="flex items-center gap-2 sm:gap-3 shrink-0">
        <RouterLink
          :to="{ name: 'login' }"
          class="text-sm font-semibold text-muted-foreground hover:text-foreground transition-colors duration-quick px-2 py-1"
        >
          Log in
        </RouterLink>
        <RouterLink
          :to="{ name: 'signup' }"
          class="inline-flex h-10 items-center justify-center gap-1 rounded-pill bg-primary px-4 text-sm font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
        >
          Sign up
        </RouterLink>
      </nav>
    </div>
  </header>
</template>
