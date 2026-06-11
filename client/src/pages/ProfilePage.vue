<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  CheckCircle2,
  Flame,
  LogOut,
  Mail,
  MapPin,
  Sparkles,
  Trophy,
  UserCircle,
  Zap,
} from 'lucide-vue-next'
import { updateProfile } from '@/lib/lessonsApi'
import { extractMessage } from '@/lib/api'
import { useUserStore } from '@/stores/me'
import { useAuthStore } from '@/stores/auth'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const me = useUserStore()
const auth = useAuthStore()
const router = useRouter()

const displayName = ref('')
const hometown = ref('')
const isSaving = ref(false)
const saveError = ref<string | null>(null)
const saveOk = ref(false)
const isLoggingOut = ref(false)

async function handleLogout() {
  isLoggingOut.value = true
  try {
    me.clear()
    await auth.logout()
    router.push({ name: 'landing' })
  } finally {
    isLoggingOut.value = false
  }
}

const fullName = computed(() =>
  auth.user ? `${auth.user.first_name} ${auth.user.last_name}`.trim() : ''
)
const email = computed(() => auth.user?.email ?? '')

const dirty = computed(() => {
  return (
    displayName.value !== (me.profile?.display_name ?? '') ||
    hometown.value !== (me.profile?.hometown ?? '')
  )
})

function syncFormFromStore() {
  displayName.value = me.profile?.display_name ?? ''
  hometown.value = me.profile?.hometown ?? ''
}

watch(() => me.profile, syncFormFromStore)

onMounted(async () => {
  if (!me.profile) await me.fetch()
  syncFormFromStore()
})

async function onSubmit() {
  if (!dirty.value) return
  saveError.value = null
  saveOk.value = false
  isSaving.value = true
  try {
    const updated = await updateProfile(displayName.value.trim(), hometown.value.trim())
    me.profile = updated
    saveOk.value = true
    setTimeout(() => (saveOk.value = false), 2500)
  } catch (e) {
    saveError.value = extractMessage(e)
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-10">
    <!-- Header -->
    <header class="flex items-center gap-4">
      <div
        class="flex h-16 w-16 items-center justify-center rounded-pill bg-primary/15 text-primary"
      >
        <UserCircle class="h-9 w-9" />
      </div>
      <div class="space-y-0.5">
        <p class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
          Your profile
        </p>
        <h1 class="font-display text-3xl sm:text-4xl font-semibold leading-tight">
          {{ fullName || 'Your details' }}
        </h1>
      </div>
    </header>

    <!-- Stats strip -->
    <section class="grid grid-cols-3 gap-3 sm:max-w-2xl">
      <div class="sunlit-card p-4 sm:p-5 space-y-1">
        <div
          class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground font-semibold"
        >
          <Trophy class="h-3.5 w-3.5" />
          Vocab
        </div>
        <div class="numeric-display text-3xl sm:text-5xl text-foreground">
          {{ me.vocabCounter }}
        </div>
      </div>
      <div class="sunlit-card p-4 sm:p-5 space-y-1">
        <div
          class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground font-semibold"
        >
          <Zap class="h-3.5 w-3.5" />
          XP
        </div>
        <div class="numeric-display text-3xl sm:text-5xl text-primary">{{ me.xp }}</div>
      </div>
      <div class="sunlit-card p-4 sm:p-5 space-y-1">
        <div
          class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground font-semibold"
        >
          <Flame class="h-3.5 w-3.5" />
          Streak
        </div>
        <div class="numeric-display text-3xl sm:text-5xl text-foreground/40">
          {{ me.streak.count }}
        </div>
      </div>
    </section>

    <!-- Identity (read-only) -->
    <section class="space-y-3">
      <h2 class="font-display text-xl font-semibold">Identity</h2>
      <div class="sunlit-card p-6 space-y-5">
        <div class="space-y-1">
          <p class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Name</p>
          <p class="text-lg font-semibold">{{ fullName || '—' }}</p>
        </div>
        <div class="space-y-1">
          <p
            class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-muted-foreground font-semibold"
          >
            <Mail class="h-3.5 w-3.5" />
            Email
          </p>
          <p class="text-lg">{{ email || '—' }}</p>
          <p class="text-xs italic text-muted-foreground">
            Want to change name or email? That's coming in a later phase.
          </p>
        </div>

        <!-- Sign-out lives here so it's a deliberate two-step action, not a one-tap
             slip on the dashboard. -->
        <div class="pt-4 border-t border-border/60">
          <button
            type="button"
            :disabled="isLoggingOut"
            class="inline-flex items-center gap-2 rounded-soft border-2 border-border bg-card px-4 py-2 text-sm font-semibold text-foreground transition-colors duration-quick ease-quick hover:border-destructive hover:bg-destructive/10 hover:text-destructive focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-destructive focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:opacity-50 disabled:cursor-not-allowed"
            @click="handleLogout"
          >
            <LogOut class="h-4 w-4" />
            {{ isLoggingOut ? 'Logging out…' : 'Log out' }}
          </button>
        </div>
      </div>
    </section>

    <!-- Editable: display_name + hometown -->
    <section class="space-y-3">
      <h2 class="font-display text-xl font-semibold">In-lesson details</h2>
      <p class="text-sm text-muted-foreground max-w-prose">
        These show up inside lessons — when Sofía at the café asks where you're from, when a
        dialogue uses your nickname. Real values make the practice feel real.
      </p>
      <form class="sunlit-card p-6 space-y-5" @submit.prevent="onSubmit">
        <div class="space-y-2">
          <Label for="display_name">Display name</Label>
          <Input
            id="display_name"
            v-model="displayName"
            type="text"
            maxlength="120"
            placeholder="What should we call you?"
            autocomplete="given-name"
          />
        </div>

        <div class="space-y-2">
          <Label for="hometown" class="flex items-center gap-1.5">
            <MapPin class="h-3.5 w-3.5" />
            Hometown
          </Label>
          <Input
            id="hometown"
            v-model="hometown"
            type="text"
            maxlength="120"
            placeholder="Where are you from?"
            autocomplete="address-level2"
          />
        </div>

        <div v-if="saveError" class="coach-feedback text-sm">{{ saveError }}</div>

        <Transition
          enter-active-class="transition duration-quick ease-out"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-quick ease-out"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="saveOk"
            class="flex items-center gap-2 rounded-soft border border-success/40 bg-success/10 px-3 py-2 text-sm font-semibold text-success"
            style="color: hsl(var(--success))"
          >
            <CheckCircle2 class="h-4 w-4" />
            Saved.
          </div>
        </Transition>

        <button
          type="submit"
          :disabled="isSaving || !dirty"
          class="inline-flex h-12 items-center justify-center rounded-soft bg-primary px-6 font-semibold text-primary-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
        >
          {{ isSaving ? 'Saving…' : 'Save' }}
        </button>
      </form>
    </section>

    <!-- Badges -->
    <section class="space-y-3">
      <h2 class="font-display text-xl font-semibold">Badges</h2>

      <div v-if="me.badges.length === 0" class="sunlit-card p-6 text-center space-y-2">
        <Sparkles class="mx-auto h-8 w-8 text-muted-foreground" />
        <p class="text-sm text-muted-foreground italic">
          No badges yet. Finish a unit to earn your first.
        </p>
      </div>

      <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="badge in me.badges" :key="badge.key" class="sunlit-card p-5 space-y-2">
          <div class="flex items-center gap-2">
            <Sparkles class="h-4 w-4 text-primary" />
            <p class="font-display text-lg font-semibold leading-tight">{{ badge.title }}</p>
          </div>
          <p class="text-sm text-muted-foreground">{{ badge.description }}</p>
        </div>
      </div>
    </section>
  </div>
</template>
