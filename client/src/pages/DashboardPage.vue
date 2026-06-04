<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'

const auth = useAuthStore()
const firstName = computed(() => auth.user?.first_name ?? '')
const fullName = computed(() =>
  `${auth.user?.first_name ?? ''} ${auth.user?.last_name ?? ''}`.trim()
)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Welcome back, {{ firstName }}.</h1>
      <p class="text-sm text-muted-foreground">You're signed in to your Laravel + Vue account.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Account</CardTitle>
          <CardDescription>Your profile details on file.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-3 text-sm">
          <div>
            <div class="text-xs tracking-wide uppercase text-muted-foreground">Name</div>
            <div class="font-medium">{{ fullName || '—' }}</div>
          </div>
          <div>
            <div class="text-xs tracking-wide uppercase text-muted-foreground">Email</div>
            <div class="font-medium">{{ auth.user?.email ?? '—' }}</div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>What's next</CardTitle>
          <CardDescription>This is a clean Vue 3 + Pinia + Sanctum scaffold.</CardDescription>
        </CardHeader>
        <CardContent class="text-sm text-muted-foreground">
          Build your features on top of this. The auth flow (login, signup, forgot &amp; reset
          password) is wired end-to-end against the Laravel backend.
        </CardContent>
      </Card>
    </div>
  </div>
</template>
