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
      </div>
    </header>

    <div class="max-w-4xl mx-auto p-6">

      <!-- Estado pendiente -->
      <div v-if="estadoPendiente" class="text-center py-16">
        <div class="text-4xl mb-4">⏳</div>
        <h2 class="text-lg font-semibold text-gray-700 mb-2">{{ estadoPendiente.titulo }}</h2>
        <p class="text-sm text-gray-400">{{ estadoPendiente.mensaje }}</p>
        <router-link to="/alumno/dashboard" class="inline-block mt-6 text-sm text-blue-600 underline">← Volver al dashboard</router-link>
      </div>

      <template v-else-if="datos">

        <!-- Breadcrumb -->
        <div class="text-xs text-gray-400 mb-4">
          <router-link to="/alumno/dashboard" class="underline hover:text-gray-600">Mis Asignaturas</router-link>
          / {{ datos.escenario?.asignatura?.nombre }}
          / Resultados
        </div>

        <!-- Cabecera resultados -->
        <div class="bg-white border-2 border-gray-200 rounded-lg p-6 mb-5 grid grid-cols-[1fr_auto] gap-6 items-center">
          <div>
            <h1 class="text-xl font-semibold text-gray-800 mb-1">{{ datos.escenario?.titulo }}</h1>
            <p class="text-sm text-gray-400 mb-3">
              {{ datos.escenario?.asignatura?.nombre }}
            </p>
            <div class="flex gap-2 flex-wrap">
              <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full">
                📅 {{ formatFecha(datos.finalizacion_at) }}
              </span>
              <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full">
                📨 {{ datos.num_mensajes }} mensajes
              </span>
              <span class="text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full">
                📊 Dificultad: {{ dificultadLabel(datos.escenario?.nivel_dificultad) }}
              </span>
            </div>
          </div>
          <div class="text-center px-8 py-4 bg-gray-50 border-2 border-gray-200 rounded-xl">
            <div class="text-xs text-gray-400 uppercase font-bold tracking-wide mb-1">Nota final</div>
            <div class="text-4xl font-bold text-gray-700">{{ datos.resultado?.final_calificacion }}</div>
            <div class="text-sm text-gray-400">/ 10</div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex bg-white border-2 border-gray-200 rounded-lg overflow-hidden mb-5">
          <button v-for="(tab, i) in tabs" :key="i" @click="tabActivo = i"
            :class="['flex-1 py-3.5 text-sm font-medium border-r last:border-0 border-gray-100 transition-colors',
              tabActivo === i ? 'bg-gray-100 text-gray-800 font-semibold' : 'text-gray-400 hover:bg-gray-50']">
            {{ tab }}
          </button>
        </div>

        <!-- ── Tab 0: Mapa de Descubrimiento ─────────────────────────── -->
        <div v-if="tabActivo === 0" class="bg-white border-2 border-gray-200 rounded-lg p-6">
          <h2 class="text-base font-semibold text-gray-700 mb-1">Mapa de Descubrimiento</h2>
          <p class="text-xs text-gray-400 mb-6">Qué información lograste obtener del personaje y qué se te escapó</p>

          <!-- Barras resumen -->
          <div class="grid grid-cols-2 gap-5 mb-6">
            <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
              <h3 class="text-xs text-gray-400 uppercase font-bold tracking-wide mb-2">Información explícita</h3>
              <div class="text-3xl font-bold text-gray-600 mb-0.5">{{ pctExplicita }}%</div>
              <div class="text-xs text-gray-400 mb-2">{{ descubiertos.length }} de {{ descubiertos.length + noDescubiertos.length }} elementos descubiertos</div>
              <div class="h-2.5 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gray-500 rounded-full" :style="{ width: pctExplicita + '%' }"></div>
              </div>
            </div>
            <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
              <h3 class="text-xs text-gray-400 uppercase font-bold tracking-wide mb-2">Información latente</h3>
              <div class="text-3xl font-bold text-gray-600 mb-0.5">{{ pctLatente }}%</div>
              <div class="text-xs text-gray-400 mb-2">{{ descubiertos.length }} de {{ descubiertos.length + noDescubiertos.length }} elementos</div>
              <div class="h-2.5 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gray-500 rounded-full" :style="{ width: pctLatente + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- Grid descubiertos / no descubiertos -->
          <div class="grid grid-cols-2 gap-5">
            <div>
              <h3 class="text-xs text-gray-500 uppercase font-bold mb-3 pb-2 border-b border-gray-100">✓ Descubiertos</h3>
              <div v-if="descubiertos.length === 0" class="text-xs text-gray-400 italic">Ninguno</div>
              <div v-for="item in descubiertos" :key="item"
                class="flex items-start gap-2 bg-gray-50 border border-gray-100 border-l-4 border-l-green-400 rounded-md p-3 mb-2">
                <div class="w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">✓</div>
                <span class="text-xs text-gray-600">{{ item }}</span>
              </div>
            </div>
            <div>
              <h3 class="text-xs text-gray-500 uppercase font-bold mb-3 pb-2 border-b border-gray-100">✗ No descubiertos</h3>
              <div v-if="noDescubiertos.length === 0" class="text-xs text-gray-400 italic">¡Todo descubierto!</div>
              <div v-for="item in noDescubiertos" :key="item"
                class="flex items-start gap-2 bg-red-50 border border-red-100 border-l-4 border-l-red-400 rounded-md p-3 mb-2">
                <div class="w-5 h-5 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">✗</div>
                <span class="text-xs text-gray-600">{{ item }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Tab 1: Competencias ────────────────────────────────────── -->
        <div v-if="tabActivo === 1" class="bg-white border-2 border-gray-200 rounded-lg p-6">
          <h2 class="text-base font-semibold text-gray-700 mb-5">Competencias evaluadas</h2>
          <div v-if="!datos.resultado?.final_competencias?.length" class="text-sm text-gray-400 text-center py-8">Sin datos de competencias.</div>
          <div v-else class="space-y-4">
            <div v-for="comp in datos.resultado.final_competencias" :key="comp.competencia_id"
              class="border border-gray-100 rounded-lg p-4">
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-semibold text-gray-700">{{ nombreCompetencia(comp.competencia_id) }}</span>
                <span class="text-lg font-bold text-gray-600">{{ comp.puntuacion }} <span class="text-xs text-gray-400 font-normal">/ 10</span></span>
              </div>
              <div class="h-2 bg-gray-100 rounded-full overflow-hidden mb-2">
                <div class="h-full bg-gray-400 rounded-full" :style="{ width: (comp.puntuacion * 10) + '%' }"></div>
              </div>
              <p v-if="comp.comentario" class="text-xs text-gray-400 italic">{{ comp.comentario }}</p>
            </div>
          </div>
        </div>

        <!-- ── Tab 2: Feedback ────────────────────────────────────────── -->
        <div v-if="tabActivo === 2" class="bg-white border-2 border-gray-200 rounded-lg p-6">
          <h2 class="text-base font-semibold text-gray-700 mb-4">Feedback del profesor</h2>
          <div class="bg-gray-50 border border-gray-100 rounded-lg p-5">
            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ datos.resultado?.final_feedback ?? 'Sin feedback.' }}</p>
          </div>
        </div>

        <!-- ── Tab 3: Transcripción ───────────────────────────────────── -->
        <div v-if="tabActivo === 3" class="bg-white border-2 border-gray-200 rounded-lg p-6">
          <h2 class="text-base font-semibold text-gray-700 mb-4">Transcripción de la conversación</h2>
          <div class="space-y-3 max-h-[600px] overflow-y-auto">
            <div v-for="msg in datos.mensajes" :key="msg.id"
              :class="['text-xs rounded-lg p-3 leading-relaxed',
                msg.emisor === 'alumno'
                  ? 'bg-gray-100 text-gray-600 ml-8'
                  : 'bg-gray-50 border border-gray-100 text-gray-500 mr-8']">
              <div class="text-xs text-gray-400 font-semibold uppercase mb-1">
                {{ msg.emisor === 'alumno' ? 'Tú' : 'Personaje (Agente)' }}
              </div>
              {{ msg.contenido }}
            </div>
          </div>
        </div>

      </template>
    </div>
  </div>
</template>

<script setup>
/**
 * ResultadosView — WF-17 — CU-30.
 * 4 tabs: Mapa de Descubrimiento · Competencias · Feedback · Transcripción.
 * Cabecera con nota final prominente.
 * Referencia: docs/prototipos/17_resultados_descubrimiento.html
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'

const route    = useRoute()
const router   = useRouter()
const auth     = useAuthStore()
const sesionId = Number(route.params.sesionId)

const datos           = ref(null)
const estadoPendiente = ref(null)
const competencias    = ref([])
const tabActivo       = ref(0)

const tabs = ['📍 Mapa de Descubrimiento', '🎯 Competencias', '💬 Feedback del profesor', '📄 Transcripción']

async function cargar() {
  try {
    const [resData, compData] = await Promise.all([
      api.get(`/sesiones/${sesionId}/resultado`),
      api.get('/competencias'),
    ])
    competencias.value = compData.data.data ?? []

    if (resData.data.estado !== 'evaluado') {
      estadoPendiente.value = {
        titulo:  resData.data.estado === 'procesando' ? 'La IA está generando tu evaluación' : 'Evaluación pendiente',
        mensaje: resData.data.estado === 'procesando'
          ? 'Tu profesor recibirá el borrador en unos minutos. Vuelve más tarde.'
          : 'Tu profesor aún no ha publicado la calificación.',
      }
    } else {
      datos.value = resData.data
    }
  } catch (e) {
    const status = e.response?.status
    if (status === 403 || status === 404) {
      estadoPendiente.value = {
        titulo:  'No se puede acceder a este resultado',
        mensaje: status === 403 ? 'No tienes permisos para ver este resultado.' : 'El resultado no existe.',
      }
    } else {
      // Error de red u otro — redirigir al dashboard con contexto
      router.push('/alumno/dashboard')
    }
  }
}

// ── Mapa de descubrimiento ────────────────────────────────────────────────────
const descubiertos  = computed(() => datos.value?.resultado?.mapa_descubrimiento?.descubierto  ?? [])
const noDescubiertos = computed(() => datos.value?.resultado?.mapa_descubrimiento?.no_descubierto ?? [])

const pctLatente = computed(() => {
  const total = descubiertos.value.length + noDescubiertos.value.length
  return total > 0 ? Math.round((descubiertos.value.length / total) * 100) : 0
})
const pctExplicita = computed(() => 100) // explícita siempre descubierta

// ── Helpers ───────────────────────────────────────────────────────────────────
function nombreCompetencia(id) {
  return competencias.value.find(c => c.id === id)?.nombre ?? `Competencia ${id}`
}
function dificultadLabel(d) {
  return { facil: 'Fácil', medio: 'Medio', dificil: 'Difícil' }[d] ?? '—'
}
function formatFecha(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(() => cargar())
</script>
