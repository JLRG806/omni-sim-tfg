import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useAuthStore } from './stores/authStore'
import './style.css'

/**
 * Punto de entrada de la SPA.
 * Bootstrap async: restaura la sesión desde el backend antes de montar el
 * router para que el guard de roles tenga el usuario disponible desde la
 * primera navegación (evita flasheo de /login en recargas con sesión activa).
 */
async function bootstrap() {
  const app = createApp(App)
  const pinia = createPinia()

  app.use(pinia)

  const auth = useAuthStore()
  auth.restaurarToken()

  if (auth.token) {
    await auth.restaurarSesion().catch(() => auth.limpiarSesion())
  }

  app.use(router)
  app.mount('#app')
}

bootstrap()
