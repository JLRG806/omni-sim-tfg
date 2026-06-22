<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-10 w-full max-w-md text-center">

      <div class="w-20 h-20 rounded-full bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center mx-auto mb-5 text-gray-400 text-xs">
        Logo
      </div>

      <h1 class="text-2xl font-semibold text-gray-800 mb-1">OmniSim</h1>
      <p class="text-sm text-gray-500 mb-8">Plataforma de simulación multidisciplinar</p>

      <form @submit.prevent="handleLogin" novalidate>
        <div class="mb-5 text-left">
          <label class="block text-sm font-semibold text-gray-600 mb-1">
            Correo electrónico
          </label>
          <input
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="usuario@universidad.edu"
            class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
            :class="{ 'border-red-400': error }"
          />
        </div>

        <div class="mb-5 text-left">
          <label class="block text-sm font-semibold text-gray-600 mb-1">
            Contraseña
          </label>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••"
            class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
            :class="{ 'border-red-400': error }"
          />
        </div>

        <p v-if="error" class="text-sm text-red-500 mb-4">{{ error }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold rounded-lg text-sm transition-colors"
        >
          {{ loading ? 'Iniciando sesión…' : 'Iniciar Sesión' }}
        </button>
      </form>

      <router-link to="/recuperar-cuenta" class="block mt-4 text-sm text-gray-500 underline hover:text-gray-700">
        ¿Olvidaste tu contraseña?
      </router-link>

      <hr class="my-5 border-gray-100" />

      <p class="text-xs text-gray-400">Acceso exclusivo para usuarios registrados</p>
    </div>
  </div>
</template>

<script setup>
/**
 * LoginView — CU-01 Iniciar Sesión.
 * Redirige según rol: admin → /admin/usuarios · profesor → /profesor/dashboard · alumno → /alumno/dashboard
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const auth = useAuthStore()

const email    = ref('')
const password = ref('')
const loading  = ref(false)
const error    = ref('')

const rolRedirect = {
  admin:    '/admin/usuarios',
  profesor: '/profesor/dashboard',
  alumno:   '/alumno/dashboard',
}

async function handleLogin() {
  error.value   = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    await router.push(rolRedirect[auth.user.rol] ?? '/')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Error al iniciar sesión'
  } finally {
    loading.value = false
  }
}
</script>
