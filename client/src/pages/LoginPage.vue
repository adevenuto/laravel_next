<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useApi } from '@/composables/useApi'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from '@/components/ui/card'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const email = ref('')
const password = ref('')

const { error, loading, execute } = useApi(auth.login)

const banner = computed(() => {
  if (route.query.registered === '1') return 'Account created. Please log in.'
  if (route.query.reset === '1') return 'Password updated. Please log in with your new password.'
  return null
})

async function onSubmit() {
  await execute({ email: email.value, password: password.value })
  if (!error.value && auth.isAuthenticated) {
    router.push({ name: 'dashboard' })
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Sign in</CardTitle>
      <CardDescription>Enter your email and password to continue.</CardDescription>
    </CardHeader>
    <form @submit.prevent="onSubmit">
      <CardContent class="space-y-4">
        <div
          v-if="banner"
          class="rounded-soft border border-success/40 bg-success/10 px-3 py-2 text-sm text-foreground"
        >
          {{ banner }}
        </div>
        <div
          v-if="error"
          class="rounded-soft border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive font-medium"
        >
          {{ error }}
        </div>
        <div class="space-y-2">
          <Label for="email">Email</Label>
          <Input id="email" v-model="email" type="email" required autocomplete="email" />
        </div>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <Label for="password">Password</Label>
            <router-link
              :to="{ name: 'forgot-password' }"
              class="text-xs text-muted-foreground hover:underline"
            >
              Forgot password?
            </router-link>
          </div>
          <Input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
          />
        </div>
      </CardContent>
      <CardFooter class="flex-col space-y-2">
        <Button type="submit" class="w-full" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </Button>
        <p class="text-center text-xs text-muted-foreground">
          Don't have an account?
          <router-link :to="{ name: 'signup' }" class="font-medium text-foreground hover:underline">
            Create one
          </router-link>
        </p>
      </CardFooter>
    </form>
  </Card>
</template>
