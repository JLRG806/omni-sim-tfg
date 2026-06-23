<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-gray-200 border border-dashed border-gray-400 flex items-center justify-center text-xs text-gray-500">Logo</div>
        <span class="font-bold text-gray-800">OmniSim</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Alumno</span>
        <span class="text-sm text-gray-600">{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-sm border border-gray-300 px-3 py-1 rounded hover:bg-gray-50">Cerrar sesión</button>
      </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto w-full p-6">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Bienvenido, {{ alumno?.name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Selecciona un escenario para iniciar una simulación</p>
      </div>

      <div v-if="loading" class="text-center py-16 text-gray-400 text-sm">Cargando…</div>
      <div v-else-if="error" class="text-center py-16 text-red-500 text-sm">{{ error }}</div>

      <template v-else>
        <div v-if="asignaturas.length === 0"
          class="bg-white border-2 border-dashed border-gray-300 rounded-xl p-10 text-center text-gray-400 text-sm">
          No estás matriculado en ninguna asignatura todavía.
        </div>

        <div v-for="asig in asignaturas" :key="asig.id" class="mb-8">
          <div class="flex items-center gap-2 mb-3">
            <span class="font-mono text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ asig.codigo }}</span>
            <h2 class="text-base font-semibold text-gray-800">{{ asig.nombre }}</h2>
            <span class="text-xs text-gray-400">· {{ asig.profesor?.name }}</span>
          </div>

          <div v-if="asig.escenarios.length === 0" class="text-sm text-gray-400 italic ml-2">
            Sin escenarios publicados aún.
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <router-link
              v-for="esc in asig.escenarios"
              :key="esc.id"
              :to="`/alumno/asignaturas/${asig.id}`"
              class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-blue-300 hover:shadow-sm transition-all block"
            >
              <h3 class="text-sm font-semibold text-gray-800 mb-1">{{ esc.titulo }}</h3>
              <p class="text-xs text-gray-500 mb-3">{{ esc.area_conocimiento }}</p>
              <p class="text-xs text-gray-400 line-clamp-2">{{ esc.descripcion_situacion }}</p>
              <div class="mt-3 flex gap-2">
                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-medium">Iniciar simulación</span>
              </div>
            </router-link>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script setup>
/**
 * DashboardAlumnoView — CU-25 Nav Dashboard Alumno.
 * Muestra asignaturas matriculadas con escenarios publicados disponibles.
 * Cada escenario lleva a VistaAsignaturaAlumnoView para iniciar/retomar simulación.
 */
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'

const router = useRouter()
const auth   = useAuthStore()

const alumno      = ref(null)
const asignaturas = ref([])
const loading     = ref(false)
const error       = ref('')

async function cargar() {
  loading.value = true
  error.value   = ''
  try {
    const { data } = await api.get('/alumno/dashboard')
    alumno.value      = data.alumno
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
