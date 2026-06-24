<template>
  <div class="min-h-screen bg-gray-100">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-gray-200 border border-dashed border-gray-400 flex items-center justify-center text-xs text-gray-500">Logo</div>
        <span class="font-bold text-gray-800">OmniSim</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded">Alumno</span>
        <span class="text-sm text-gray-600">{{ auth.user?.email }}</span>
        <button @click="handleLogout" class="text-sm border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-50 text-gray-600">
          Cerrar sesión
        </button>
      </div>
    </header>

    <main class="max-w-4xl mx-auto p-6">

      <!-- Bienvenida -->
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Hola, {{ alumno?.name }}</h1>
        <p class="text-sm text-gray-500 mt-1">Continúa practicando entrevistas con tus escenarios</p>
      </div>

      <div v-if="loading" class="text-center py-16 text-gray-400 text-sm">Cargando...</div>
      <div v-else-if="error" class="text-center py-16 text-red-500 text-sm">{{ error }}</div>

      <template v-else>

        <!-- Stats globales -->
        <div class="grid grid-cols-3 gap-3 mb-8">
          <div class="bg-white border-2 border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ stats.sesiones_realizadas }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-wide mt-1">Sesiones realizadas</div>
          </div>
          <div class="bg-white border-2 border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ stats.en_curso }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-wide mt-1">En curso</div>
          </div>
          <div class="bg-white border-2 border-gray-200 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-600">{{ stats.nota_media ?? '—' }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-wide mt-1">Nota media</div>
          </div>
        </div>

        <!-- Mis Asignaturas -->
        <h2 class="text-base font-semibold text-gray-600 mb-4">Mis Asignaturas</h2>

        <div v-if="asignaturas.length === 0"
          class="bg-white border-2 border-dashed border-gray-300 rounded-xl p-10 text-center text-gray-400 text-sm">
          No estás matriculado en ninguna asignatura todavía.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="asig in asignaturas"
            :key="asig.id"
            @click="$router.push(`/alumno/asignaturas/${asig.id}`)"
            class="bg-white border-2 border-gray-200 rounded-lg p-5 cursor-pointer hover:border-gray-400 transition-colors"
          >
            <!-- Código + Badge -->
            <div class="flex items-start justify-between mb-3">
              <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded font-mono">{{ asig.codigo }}</span>
              <span v-if="asig.badge"
                :class="asig.badge.tipo === 'en_curso'
                  ? 'bg-amber-100 text-amber-700'
                  : 'bg-green-100 text-green-700'"
                class="text-xs font-semibold px-2 py-1 rounded-full">
                {{ asig.badge.texto }}
              </span>
            </div>

            <!-- Nombre y profesor -->
            <p class="text-base font-semibold text-gray-800 mb-1">{{ asig.nombre }}</p>
            <p class="text-xs text-gray-400 mb-4">Prof. {{ asig.profesor.name }}</p>

            <!-- Progreso -->
            <div class="border-t border-gray-100 pt-3">
              <div class="flex justify-between items-center mb-1.5">
                <span class="text-xs text-gray-400">Progreso en escenarios</span>
                <span class="text-xs font-semibold text-gray-500">{{ asig.completados }} / {{ asig.total_escenarios }}</span>
              </div>
              <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden mb-3">
                <div class="h-full bg-gray-400 rounded-full transition-all"
                  :style="{ width: asig.total_escenarios > 0 ? `${(asig.completados / asig.total_escenarios) * 100}%` : '0%' }">
                </div>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-xs text-gray-400">Nota media</span>
                <span class="text-xs font-semibold text-gray-500">{{ asig.nota_media ?? '—' }}</span>
              </div>
            </div>
          </div>
        </div>

      </template>
    </main>
  </div>
</template>

<script setup>
/**
 * DashboardAlumnoView — CU-25 Nav Dashboard Alumno.
 * Muestra stats globales + asignaturas con progreso.
 * Click en asignatura → VistaAsignaturaAlumnoView.
 * Referencia: docs/prototipos/14_dashboard_alumno.html
 */
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'

const router = useRouter()
const auth   = useAuthStore()

const alumno      = ref(null)
const stats       = ref({ sesiones_realizadas: 0, en_curso: 0, nota_media: null })
const asignaturas = ref([])
const loading     = ref(true)
const error       = ref('')

async function cargar() {
  loading.value = true
  error.value   = ''
  try {
    const { data } = await api.get('/alumno/dashboard')
    alumno.value      = data.alumno
    stats.value       = data.stats
    asignaturas.value = data.asignaturas
  } catch {
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
