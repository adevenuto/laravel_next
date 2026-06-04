<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { LogOut } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'

const auth = useAuthStore()
const router = useRouter()

const fullName = computed(() => {
  if (!auth.user) return ''
  return `${auth.user.first_name} ${auth.user.last_name}`.trim()
})

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="border-b bg-card">
    <div class="flex items-center justify-between w-full max-w-5xl p-4 mx-auto">
      <div class="font-semibold">Laravel + Vue</div>
      <div class="flex items-center gap-4">
        <span v-if="fullName" class="text-sm text-muted-foreground">{{ fullName }}</span>
        <Button variant="outline" size="sm" :disabled="auth.isLoading" @click="handleLogout">
          <LogOut class="w-4 h-4 mr-2" />
          Logout
        </Button>
      </div>
    </div>
  </header>
</template>
