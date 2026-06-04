<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
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
const router = useRouter()

const first_name = ref('')
const last_name = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')

const { error, loading, execute } = useApi(auth.register)

async function onSubmit() {
  await execute({
    first_name: first_name.value,
    last_name: last_name.value,
    email: email.value,
    password: password.value,
    password_confirmation: password_confirmation.value,
  })
  if (!error.value && auth.isAuthenticated) {
    router.push({ name: 'dashboard' })
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Create account</CardTitle>
      <CardDescription>Get started in under a minute.</CardDescription>
    </CardHeader>
    <form @submit.prevent="onSubmit">
      <CardContent class="space-y-4">
        <div
          v-if="error"
          class="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive"
        >
          {{ error }}
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-2">
            <Label for="first_name">First name</Label>
            <Input id="first_name" v-model="first_name" required autocomplete="given-name" />
          </div>
          <div class="space-y-2">
            <Label for="last_name">Last name</Label>
            <Input id="last_name" v-model="last_name" required autocomplete="family-name" />
          </div>
        </div>
        <div class="space-y-2">
          <Label for="email">Email</Label>
          <Input id="email" v-model="email" type="email" required autocomplete="email" />
        </div>
        <div class="space-y-2">
          <Label for="password">Password</Label>
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
      <CardFooter class="flex-col space-y-2">
        <Button type="submit" class="w-full" :disabled="loading">
          {{ loading ? 'Creating…' : 'Create account' }}
        </Button>
        <p class="text-center text-xs text-muted-foreground">
          Already have an account?
          <router-link :to="{ name: 'login' }" class="font-medium text-foreground hover:underline">
            Sign in
          </router-link>
        </p>
      </CardFooter>
    </form>
  </Card>
</template>
