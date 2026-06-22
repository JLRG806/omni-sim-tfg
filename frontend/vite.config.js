import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      // Permite importar con '@/...' en lugar de rutas relativas frágiles
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    // 0.0.0.0 es obligatorio para que Nginx pueda alcanzar Vite desde otro contenedor Docker
    host: '0.0.0.0',
    port: 5173,
  },
})
