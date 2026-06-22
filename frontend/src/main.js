import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './style.css'

/**
 * Punto de entrada de la SPA.
 * Orden de plugins: Pinia debe montarse antes que el Router porque
 * el guard de navegación (router/index.js) accede a useAuthStore().
 */
const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
