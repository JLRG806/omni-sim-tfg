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
        <button @click="handleLogout" class="text-sm border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-50 text-gray-600">Cerrar sesión</button>
      </div>
    </header>

    <div class="max-w-4xl mx-auto p-6">

      <div v-if="loading" class="text-center py-16 text-gray-400 text-sm">Cargando...</div>
      <div v-else-if="error" class="text-center py-16 text-red-500 text-sm">{{ error }}</div>

      <template v-else>

        <!-- Breadcrumb -->
        <div class="text-xs text-gray-400 mb-4">
          <router-link to="/alumno/dashboard" class="underline hover:text-gray-600">Mis Asignaturas</router-link>
          / {{ asignatura?.nombre }}
        </div>

        <!-- Cabecera asignatura -->
        <div class="bg-white border-2 border-gray-200 rounded-lg px-6 py-5 mb-6 flex justify-between items-center">
          <div>
            <h1 class="text-xl font-semibold text-gray-800 mb-1">{{ asignatura?.nombre }}</h1>
            <p class="text-sm text-gray-400">{{ asignatura?.descripcion }} · Prof. {{ asignatura?.profesor?.name }}</p>
          </div>
          <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded font-mono">{{ asignatura?.codigo }}</span>
        </div>

        <!-- Tabs -->
        <div class="flex bg-white border-2 border-gray-200 rounded-lg overflow-hidden mb-6">
          <button v-for="(tab, i) in tabs" :key="i" @click="tabActivo = i"
            :class="['flex-1 py-3.5 text-sm font-medium border-r last:border-0 border-gray-100 transition-colors',
              tabActivo === i ? 'bg-gray-100 text-gray-800 font-semibold' : 'text-gray-400 hover:bg-gray-50']">
            {{ tab.label }}
            <span :class="['inline-block ml-1.5 text-xs px-2 py-0.5 rounded-full',
              tabActivo === i ? 'bg-gray-400 text-white' : 'bg-gray-200 text-gray-500']">
              {{ tab.count }}
            </span>
          </button>
        </div>

        <!-- ── Tab 0: DISPONIBLES ───────────────────────────────────────── -->
        <div v-if="tabActivo === 0">

          <!-- Continúa donde lo dejaste -->
          <template v-if="sesionesActivas.length > 0">
            <p class="text-sm font-semibold text-gray-600 mb-3">Continúa donde lo dejaste</p>
            <div v-for="s in sesionesActivas" :key="s.esc.id"
              class="bg-white border-2 border-gray-300 rounded-lg p-5 mb-4 flex gap-4 items-center">
              <div class="w-14 h-14 rounded-full bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center text-xs text-gray-400 flex-shrink-0">Avatar</div>
              <div class="flex-1">
                <p class="text-base font-semibold text-gray-800 mb-1">{{ s.esc.titulo }}</p>
                <p class="text-xs text-gray-400 mb-2">Personaje: {{ s.esc.perfil?.rol_identidad ?? '—' }}</p>
                <div class="flex gap-3 text-xs items-center">
                  <span :class="dificultadClass(s.esc.perfil?.nivel_dificultad)">{{ dificultadLabel(s.esc.perfil?.nivel_dificultad) }}</span>
                  <span class="text-gray-400">{{ s.sesion.num_mensajes }} mensajes intercambiados</span>
                  <span :class="s.sesion.estado === 'pausada' ? 'text-amber-600 font-medium' : 'text-green-600 font-medium'">
                    {{ s.sesion.estado === 'pausada' ? 'Pausada' : 'En curso' }}
                  </span>
                </div>
              </div>
              <button @click="retomar(s.sesion.id)"
                class="bg-gray-500 hover:bg-gray-600 text-white text-sm font-semibold px-5 py-2.5 rounded-md whitespace-nowrap">
                Retomar →
              </button>
            </div>
          </template>

          <!-- Escenarios sin sesión activa -->
          <p class="text-sm font-semibold text-gray-600 mb-3">Escenarios disponibles</p>

          <div v-if="escenariosDisponibles.length === 0"
            class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-400 text-sm">
            Todos los escenarios tienen una sesión activa o no hay escenarios disponibles.
          </div>

          <div v-for="esc in escenariosDisponibles" :key="esc.id"
            class="bg-white border-2 border-gray-200 rounded-lg p-5 mb-3 flex gap-4 items-center hover:border-gray-300 transition-colors">
            <div class="w-14 h-14 rounded-full bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center text-xs text-gray-400 flex-shrink-0">Avatar</div>
            <div class="flex-1">
              <p class="text-base font-semibold text-gray-800 mb-1">{{ esc.titulo }}</p>
              <p class="text-xs text-gray-400 mb-2">Personaje: {{ esc.perfil?.rol_identidad ?? '—' }}</p>
              <div class="flex gap-3 text-xs items-center">
                <span :class="dificultadClass(esc.perfil?.nivel_dificultad)">{{ dificultadLabel(esc.perfil?.nivel_dificultad) }}</span>
                <span class="text-gray-400">Objetivos: {{ esc.num_objetivos }}</span>
              </div>
            </div>
            <button @click="iniciar(esc.id)"
              class="bg-gray-500 hover:bg-gray-600 text-white text-sm font-semibold px-5 py-2.5 rounded-md whitespace-nowrap">
              Iniciar simulación
            </button>
          </div>
        </div>

        <!-- ── Tab 1: EN CURSO ──────────────────────────────────────────── -->
        <div v-if="tabActivo === 1">
          <div v-if="sesionesActivas.length === 0"
            class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-400 text-sm">
            No tienes ninguna simulación en curso.
          </div>
          <div v-for="s in sesionesActivas" :key="s.sesion.id"
            class="bg-white border-2 border-gray-300 rounded-lg p-5 mb-3 flex gap-4 items-center">
            <div class="w-14 h-14 rounded-full bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center text-xs text-gray-400 flex-shrink-0">Avatar</div>
            <div class="flex-1">
              <p class="text-base font-semibold text-gray-800 mb-1">{{ s.esc.titulo }}</p>
              <p class="text-xs text-gray-400 mb-2">Personaje: {{ s.esc.perfil?.rol_identidad ?? '—' }}</p>
              <div class="flex gap-3 text-xs items-center">
                <span :class="dificultadClass(s.esc.perfil?.nivel_dificultad)">{{ dificultadLabel(s.esc.perfil?.nivel_dificultad) }}</span>
                <span class="text-gray-400">{{ s.sesion.num_mensajes }} mensajes</span>
                <span :class="s.sesion.estado === 'pausada' ? 'text-amber-600 font-semibold' : 'text-green-600 font-semibold'">
                  {{ s.sesion.estado === 'pausada' ? 'Pausada' : 'En curso' }}
                </span>
              </div>
            </div>
            <button @click="retomar(s.sesion.id)"
              class="bg-gray-500 hover:bg-gray-600 text-white text-sm font-semibold px-5 py-2.5 rounded-md">
              Retomar →
            </button>
          </div>
        </div>

        <!-- ── Tab 2: COMPLETADOS ───────────────────────────────────────── -->
        <div v-if="tabActivo === 2">
          <div v-if="sesionesCompletadas.length === 0"
            class="bg-white border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-400 text-sm">
            Aún no has completado ninguna simulación.
          </div>
          <div v-for="s in sesionesCompletadas" :key="s.sesion.id"
            class="bg-white border-2 border-gray-200 rounded-lg p-5 mb-3 flex gap-4 items-center">
            <div class="w-14 h-14 rounded-full bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center text-xs text-gray-400 flex-shrink-0">Avatar</div>
            <div class="flex-1">
              <p class="text-base font-semibold text-gray-800 mb-1">{{ s.esc.titulo }}</p>
              <p class="text-xs text-gray-400 mb-2">Finalizada {{ formatFecha(s.sesion.finalizacion_at) }}</p>
              <div class="flex gap-3 items-center">
                <span v-if="s.sesion.estado === 'evaluada' && s.sesion.resultado?.publicado_at"
                  class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">Evaluada</span>
                <span v-else-if="s.sesion.estado === 'procesando'"
                  class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-500 border border-indigo-200">Procesando IA</span>
                <span v-else
                  class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">Pendiente de evaluación</span>
                <span v-if="s.sesion.resultado?.final_calificacion !== null && s.sesion.resultado?.publicado_at"
                  class="text-base font-bold text-gray-600">
                  Nota: {{ s.sesion.resultado.final_calificacion }}
                </span>
              </div>
            </div>
            <div class="flex-shrink-0">
              <button v-if="s.sesion.resultado?.publicado_at"
                @click="verResultados(s.sesion.id)"
                class="border-2 border-gray-300 text-gray-600 text-sm font-semibold px-5 py-2.5 rounded-md hover:bg-gray-50">
                Ver resultados →
              </button>
              <span v-else class="text-xs text-gray-400">Esperando al profesor</span>
            </div>
          </div>
        </div>

      </template>
    </div>
  </div>
</template>

<script setup>
/**
 * VistaAsignaturaAlumnoView — WF-15 — CU-26/27/30.
 * 3 tabs: Disponibles · En Curso · Completados.
 * Referencia: docs/prototipos/15_vista_asignatura_alumno.html
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useSesionStore } from '@/stores/sesionStore'
import api from '@/plugins/axios'

const route       = useRoute()
const router      = useRouter()
const auth        = useAuthStore()
const sesionStore = useSesionStore()
const asigId      = Number(route.params.id)

const asignatura = ref(null)
const escenarios = ref([])
const loading    = ref(true)
const error      = ref('')
const tabActivo  = ref(0)

async function cargar() {
  loading.value = true
  error.value   = ''
  try {
    const { data } = await api.get(`/alumno/asignaturas/${asigId}`)
    asignatura.value = data.asignatura
    escenarios.value = data.escenarios
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Error al cargar la asignatura'
  } finally {
    loading.value = false
  }
}

const sesionesActivas = computed(() =>
  escenarios.value.filter(e => e.sesion_activa).map(e => ({ esc: e, sesion: e.sesion_activa }))
)
const escenariosDisponibles = computed(() =>
  escenarios.value.filter(e => !e.sesion_activa)
)
const sesionesCompletadas = computed(() =>
  escenarios.value
    .flatMap(e => e.sesiones_completadas.map(s => ({ esc: e, sesion: s })))
    .sort((a, b) => new Date(b.sesion.finalizacion_at) - new Date(a.sesion.finalizacion_at))
)
const tabs = computed(() => [
  { label: 'Disponibles', count: escenariosDisponibles.value.length },
  { label: 'En Curso',    count: sesionesActivas.value.length },
  { label: 'Completados', count: sesionesCompletadas.value.length },
])

async function iniciar(escenarioId) {
  try {
    const { data } = await api.post('/sesiones', { escenario_id: escenarioId })
    sesionStore.setSesion(data.sesion)
    router.push(`/alumno/simulacion/${data.sesion.id}`)
  } catch (e) {
    if (e.response?.status === 409) {
      router.push(`/alumno/simulacion/${e.response.data.sesion_id}`)
    } else {
      alert(e.response?.data?.message ?? 'Error al iniciar la simulación')
    }
  }
}

async function retomar(sesionId) {
  try {
    const { data } = await api.patch(`/sesiones/${sesionId}/retomar`)
    sesionStore.setSesion(data.sesion)
    router.push(`/alumno/simulacion/${sesionId}`)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Error al retomar la sesión')
  }
}

function verResultados(sesionId) {
  router.push(`/alumno/sesiones/${sesionId}/resultado`)
}

function dificultadLabel(d) {
  return { facil: 'Fácil', medio: 'Medio', dificil: 'Difícil' }[d] ?? '—'
}
function dificultadClass(d) {
  const c = { facil: 'bg-green-100 text-green-700', medio: 'bg-amber-100 text-amber-700', dificil: 'bg-red-100 text-red-700' }
  return `text-xs font-semibold px-2 py-0.5 rounded-full ${c[d] ?? 'bg-gray-100 text-gray-500'}`
}
function formatFecha(iso) {
  if (!iso) return '—'
  const d    = new Date(iso)
  const diff = Math.floor((new Date() - d) / 60000)
  if (diff < 60)   return `hace ${diff} min`
  if (diff < 1440) return `hace ${Math.floor(diff / 60)} h`
  if (diff < 2880) return 'ayer'
  return `el ${d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })}`
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

onMounted(() => cargar())
</script>
