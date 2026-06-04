<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import AppLayout from '@/layouts/AppLayout.vue'

const route = useRoute()
const layout = computed(() => {
  if (route.meta.layout === 'app') return AppLayout
  return AuthLayout
})
</script>

<template>
  <component :is="layout">
    <router-view />
  </component>
</template>
