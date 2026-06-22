<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-10 w-full max-w-md text-center">

      <div class="w-15 h-15 rounded-full bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center mx-auto mb-4 text-gray-400 text-xs w-12 h-12">
        Logo
      </div>

      <h1 class="text-xl font-semibold text-gray-800 mb-1">Recuperar Cuenta</h1>
      <p class="text-sm text-gray-500 mb-6">Sigue los pasos para restablecer tu contraseña</p>

      <!-- Stepper -->
      <div class="flex items-center justify-center gap-2 mb-8">
        <div v-for="n in 3" :key="n" class="flex items-center gap-2">
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white"
            :class="step >= n ? 'bg-blue-600' : 'bg-gray-300'"
          >{{ n }}</div>
          <div v-if="n < 3" class="w-8 h-0.5" :class="step > n ? 'bg-blue-600' : 'bg-gray-300'" />
        </div>
      </div>

      <!-- Paso 1: Correo electrónico -->
      <div v-if="step === 1">
        <p class="text-sm font-semibold text-gray-700 mb-4">Introduce tu correo electrónico</p>
        <div class="mb-4 text-left">
          <label class="block text-sm font-semibold text-gray-600 mb-1">Correo electrónico</label>
          <input
            v-model="email"
            type="email"
            placeholder="usuario@universidad.edu"
            class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
          />
        </div>
        <p v-if="error" class="text-sm text-red-500 mb-3">{{ error }}</p>
        <button
          @click="enviarEnlace"
          :disabled="loading"
          class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold rounded-lg text-sm transition-colors"
        >
          {{ loading ? 'Enviando…' : 'Enviar enlace de recuperación' }}
        </button>
        <p class="text-xs text-gray-400 mt-3">Se enviará un enlace de recuperación a tu correo</p>
      </div>

      <!-- Paso 2: Enlace enviado -->
      <div v-if="step === 2">
        <div class="w-12 h-12 rounded-full bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center mx-auto mb-4 text-2xl">✉️</div>
        <p class="text-sm font-semibold text-gray-700 mb-2">Revisa tu correo</p>
        <p class="text-sm text-gray-500 mb-6">
          Hemos enviado un enlace de recuperación a <strong>{{ email }}</strong>.
          Haz clic en el enlace del correo para continuar.
        </p>
        <button @click="step = 1" class="w-full py-3 border-2 border-gray-300 text-gray-600 font-semibold rounded-lg text-sm hover:bg-gray-50">
          Volver a introducir correo
        </button>
      </div>

      <!-- Paso 3: Nueva contraseña (accedido desde el enlace del correo) -->
      <div v-if="step === 3">
        <p class="text-sm font-semibold text-gray-700 mb-4">Crea tu nueva contraseña</p>
        <div class="mb-4 text-left">
          <label class="block text-sm font-semibold text-gray-600 mb-1">Nueva contraseña</label>
          <input
            v-model="password"
            type="password"
            placeholder="Mínimo 8 caracteres"
            class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
          />
        </div>
        <div class="mb-4 text-left">
          <label class="block text-sm font-semibold text-gray-600 mb-1">Confirmar contraseña</label>
          <input
            v-model="passwordConfirmation"
            type="password"
            placeholder="Repite la contraseña"
            class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
          />
        </div>
        <p v-if="error" class="text-sm text-red-500 mb-3">{{ error }}</p>
        <p v-if="success" class="text-sm text-green-600 mb-3">{{ success }}</p>
        <button
          @click="resetPassword"
          :disabled="loading"
          class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold rounded-lg text-sm transition-colors"
        >
          {{ loading ? 'Restableciendo…' : 'Restablecer contraseña' }}
        </button>
      </div>

      <router-link to="/login" class="block mt-5 text-sm text-gray-500 underline hover:text-gray-700">
        Volver al inicio de sesión
      </router-link>
    </div>
  </div>
</template>

<script setup>
/**
 * RecuperarCuentaView — CU-03 Recuperar Cuenta.
 * Paso 1: solicita email → POST /auth/forgot-password
 * Paso 2: confirmación de envío (el usuario recibe el enlace por correo)
 * Paso 3: formulario nueva contraseña → POST /auth/reset-password
 *         (accedido desde la URL del enlace: /recuperar-cuenta/reset?token=xxx&email=xxx)
 */
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/plugins/axios'

const route  = useRoute()
const router = useRouter()

const step                = ref(1)
const email               = ref('')
const password            = ref('')
const passwordConfirmation = ref('')
const loading             = ref(false)
const error               = ref('')
const success             = ref('')

onMounted(() => {
  if (route.query.token && route.query.email) {
    email.value = route.query.email
    step.value  = 3
  }
})

async function enviarEnlace() {
  error.value   = ''
  loading.value = true
  try {
    await api.post('/auth/forgot-password', { email: email.value })
    step.value = 2
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Error al enviar el enlace'
  } finally {
    loading.value = false
  }
}

async function resetPassword() {
  error.value   = ''
  loading.value = true
  try {
    await api.post('/auth/reset-password', {
      token:                 route.query.token,
      email:                 email.value,
      password:              password.value,
      password_confirmation: passwordConfirmation.value,
    })
    success.value = 'Contraseña actualizada. Redirigiendo al login…'
    setTimeout(() => router.push('/login'), 2000)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Error al restablecer la contraseña'
  } finally {
    loading.value = false
  }
}
</script>
