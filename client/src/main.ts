import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'
import './assets/index.css'

async function bootstrap() {
  const app = createApp(App)
  app.use(createPinia())

  // Hydrate the auth store BEFORE the router can start resolving the initial route.
  // Otherwise a guarded route like /dashboard can bounce to /login while the
  // /api/user request is still in flight.
  const auth = useAuthStore()
  await auth.fetchUser().catch(() => {
    /* anonymous session is fine */
  })

  app.use(router)
  await router.isReady()

  app.mount('#app')
}

bootstrap()
