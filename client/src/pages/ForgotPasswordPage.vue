<script setup lang="ts">
import { ref } from 'vue'
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
const email = ref('')
const submitted = ref(false)

const { error, loading, execute } = useApi(auth.forgotPassword)

async function onSubmit() {
  await execute(email.value)
  // Always show generic success — backend intentionally doesn't leak.
  submitted.value = true
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Reset your password</CardTitle>
      <CardDescription>We'll email you a link to set a new one.</CardDescription>
    </CardHeader>
    <form @submit.prevent="onSubmit">
      <CardContent class="space-y-4">
        <div
          v-if="submitted && !error"
          class="rounded-soft border border-success/40 bg-success/10 px-3 py-2 text-sm text-foreground"
        >
          If an account exists for that email, a reset link has been sent.
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
      </CardContent>
      <CardFooter class="flex-col space-y-2">
        <Button type="submit" class="w-full" :disabled="loading">
          {{ loading ? 'Sending…' : 'Send reset link' }}
        </Button>
        <p class="text-center text-xs text-muted-foreground">
          Remembered it?
          <router-link :to="{ name: 'login' }" class="font-medium text-foreground hover:underline">
            Back to sign in
          </router-link>
        </p>
      </CardFooter>
    </form>
  </Card>
</template>
