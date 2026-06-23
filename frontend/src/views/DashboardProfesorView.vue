<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-gray-200 border border-dashed border-gray-400 flex items-center justify-center text-xs text-gray-500">Logo</div>
        <span class="font-bold text-gray-800">OmniSim</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Profesor</span>
        <span class="text-sm text-gray-600">{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-sm border border-gray-300 px-3 py-1 rounded hover:bg-gray-50">Cerrar sesión</button>
      </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto w-full p-6">

      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Bienvenido, {{ profesor?.name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Selecciona una asignatura para gestionar escenarios, matrículas o evaluaciones</p>
      </div>

      <div v-if="loading" class="text-center py-16 text-gray-400 text-sm">Cargando…</div>
      <div v-else-if="error" class="text-center py-16 text-red-500 text-sm">{{ error }}</div>

      <template v-else>
        <p class="text-base font-semibold text-gray-600 mb-4">Mis Asignaturas</p>

        <div v-if="asignaturas.length === 0"
          class="bg-white border-2 border-dashed border-gray-300 rounded-xl p-10 text-center text-gray-400 text-sm">
          No tienes asignaturas asignadas. Contacta con el administrador.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <router-link
            v-for="a in asignaturas"
            :key="a.id"
            :to="`/profesor/asignaturas/${a.id}`"
            class="bg-white border-2 border-gray-200 rounded-xl p-5 hover:border-blue-300 hover:shadow-sm transition-all cursor-pointer block"
          >
            <div class="flex items-start justify-between mb-3">
              <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-mono">{{ a.codigo }}</span>
            </div>
            <h3 class="text-base font-semibold text-gray-800 mb-2">{{ a.nombre }}</h3>
            <p class="text-xs text-gray-500 mb-4 line-clamp-2">{{ a.descripcion || 'Sin descripción' }}</p>
            <div class="flex gap-4 pt-3 border-t border-gray-100">
              <div class="text-center">
                <div class="text-lg font-bold text-gray-700">{{ a.stats.alumnos }}</div>
                <div class="text-xs text-gray-400 uppercase tracking-wide">Alumnos</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold text-gray-700">{{ a.stats.escenarios }}</div>
                <div class="text-xs text-gray-400 uppercase tracking-wide">Escenarios</div>
              </div>
              <div class="text-center">
                <div class="text-lg font-bold" :class="a.stats.evaluaciones_pendientes > 0 ? 'text-amber-600' : 'text-gray-700'">
                  {{ a.stats.evaluaciones_pendientes }}
                </div>
                <div class="text-xs text-gray-400 uppercase tracking-wide">Pendientes</div>
              </div>
            </div>
          </router-link>
        </div>
      </template>

    </main>
  </div>
</template>

<script setup>
/**
 * DashboardProfesorView — CU-14 Nav Dashboard Profesor.
 * Muestra las asignaturas del profesor con stats de alumnos, escenarios
 * y evaluaciones pendientes. Cada tarjeta navega a VistaAsignaturaProfesorView.
 */
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'

const router = useRouter()
const auth   = useAuthStore()

const profesor    = ref(null)
const asignaturas = ref([])
const loading     = ref(false)
const error       = ref('')

async function cargar() {
  loading.value = true
  error.value   = ''
  try {
    const { data } = await api.get('/profesor/dashboard')
    profesor.value    = data.profesor
    asignaturas.value = data.asignaturas
  } catch (e) {
    error.value = 'Error al cargar el dashboard'
  } finally {
    loading.value = false
  }
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

onMounted(() => cargar())
</script>
