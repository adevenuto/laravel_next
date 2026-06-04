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

const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''))
const emailFromQuery = computed(() =>
  typeof route.query.email === 'string' ? route.query.email : ''
)

const email = ref(emailFromQuery.value)
const password = ref('')
const password_confirmation = ref('')

const { error, loading, execute } = useApi(auth.resetPassword)

async function onSubmit() {
  await execute({
    email: email.value,
    token: token.value,
    password: password.value,
    password_confirmation: password_confirmation.value,
  })
  if (!error.value) {
    router.push({ name: 'login', query: { reset: '1' } })
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Set a new password</CardTitle>
      <CardDescription>Choose a strong password you haven't used here before.</CardDescription>
    </CardHeader>
    <form @submit.prevent="onSubmit">
      <CardContent class="space-y-4">
        <div
          v-if="!token"
          class="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive"
        >
          Missing or invalid reset token. Please request a new reset link.
        </div>
        <div
          v-if="error"
          class="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive"
        >
          {{ error }}
        </div>
        <div class="space-y-2">
          <Label for="email">Email</Label>
          <Input
            id="email"
            v-model="email"
            type="email"
            required
            :readonly="!!emailFromQuery"
            autocomplete="email"
          />
        </div>
        <div class="space-y-2">
          <Label for="password">New password</Label>
          <Input
            id="password"
            v-model="password"
            type="password"
            required
            autocomplete="new-password"
          />
        </div>
        <div class="space-y-2">
          <Label for="password_confirmation">Confirm password</Label>
          <Input
            id="password_confirmation"
            v-model="password_confirmation"
            type="password"
            required
            autocomplete="new-password"
          />
        </div>
      </CardContent>
      <CardFooter>
        <Button type="submit" class="w-full" :disabled="loading || !token">
          {{ loading ? 'Updating…' : 'Update password' }}
        </Button>
      </CardFooter>
    </form>
  </Card>
</template>
