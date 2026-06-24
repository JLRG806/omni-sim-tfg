<template>
  <div class="min-h-screen bg-gray-100">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-gray-200 border border-dashed border-gray-400 flex items-center justify-center text-xs text-gray-500">Logo</div>
        <span class="font-bold text-gray-800">OmniSim</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded">Profesor</span>
        <span class="text-sm text-gray-600">{{ auth.user?.email }}</span>
      </div>
    </header>

    <div class="max-w-6xl mx-auto p-6">

      <div v-if="loading" class="text-center py-16 text-gray-400 text-sm">Cargando evaluación...</div>
      <div v-else-if="error" class="text-center py-16 text-red-500 text-sm">{{ error }}</div>

      <template v-else>

        <!-- Breadcrumb -->
        <div class="text-xs text-gray-400 mb-4">
          <router-link to="/profesor/dashboard" class="underline hover:text-gray-600">Mis Asignaturas</router-link>
          / Evaluaciones / Emitir Calificación
        </div>

        <!-- Cabecera sesión -->
        <div class="bg-white border-2 border-gray-200 rounded-lg px-5 py-4 mb-4 flex justify-between items-center">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold text-gray-500">
              {{ iniciales(resultado?.sesion?.alumno?.name ?? '?') }}
            </div>
            <div>
              <h1 class="text-base font-semibold text-gray-800">{{ resultado?.sesion?.alumno?.name }}</h1>
              <p class="text-xs text-gray-400">{{ resultado?.sesion?.escenario?.titulo }} · Finalizada {{ formatFecha(resultado?.sesion?.mensajes?.at(-1)?.created_at) }}</p>
            </div>
          </div>
          <div class="flex gap-6 text-center text-xs text-gray-400">
            <div><div class="text-sm font-bold text-gray-600">{{ resultado?.sesion?.mensajes?.length }}</div>mensajes</div>
            <div><div class="text-sm font-bold text-gray-600">{{ resultado?.sesion?.escenario?.titulo ? '—' : '—' }}</div>duración</div>
          </div>
        </div>

        <!-- Aviso IA -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-2.5 mb-5 text-xs text-amber-700">
          ⚠ La IA ha pre-evaluado esta sesión. Revisa y ajusta cada apartado antes de publicar los resultados al alumno.
        </div>

        <!-- Layout 2 columnas -->
        <div class="grid grid-cols-2 gap-5">

          <!-- LEFT: Transcripción -->
          <div class="bg-white border-2 border-gray-200 rounded-lg p-5">
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">
              Transcripción de la conversación
            </h2>
            <div class="max-h-96 overflow-y-auto space-y-3">
              <div v-for="msg in resultado?.sesion?.mensajes" :key="msg.id"
                :class="['text-xs rounded-lg p-3 leading-relaxed',
                  msg.emisor === 'alumno'
                    ? 'bg-gray-100 text-gray-600 ml-6'
                    : 'bg-gray-50 border border-gray-100 text-gray-500 mr-6']">
                <div class="text-xs text-gray-400 font-semibold uppercase mb-1">
                  {{ msg.emisor === 'alumno' ? 'Alumno' : 'Personaje (Agente)' }}
                </div>
                {{ msg.contenido }}
              </div>
            </div>
          </div>

          <!-- RIGHT: Evaluación -->
          <div class="bg-white border-2 border-gray-200 rounded-lg p-5">
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
              Evaluación
              <span class="bg-indigo-100 text-indigo-500 text-xs font-semibold px-2 py-0.5 rounded-full">Borrador IA</span>
            </h2>

            <!-- Resumen -->
            <div class="mb-5">
              <div class="flex justify-between items-center mb-1.5">
                <label class="text-xs font-semibold text-gray-500">Resumen de la sesión</label>
                <span class="text-xs text-gray-400 italic">Editable</span>
              </div>
              <textarea v-model="form.resumen" rows="3"
                class="w-full text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-md p-3 leading-relaxed resize-none focus:outline-none focus:border-gray-400">
              </textarea>
            </div>

            <!-- Mapa de descubrimiento (solo lectura) -->
            <div class="mb-5">
              <label class="text-xs font-semibold text-gray-500 mb-2 block">Mapa de descubrimiento</label>
              <div class="space-y-2 mb-3">
                <div>
                  <div class="flex justify-between text-xs text-gray-500 font-semibold mb-1">
                    <span>Información explícita</span>
                    <span>{{ pctExplicita }}%</span>
                  </div>
                  <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gray-400 rounded-full" :style="{ width: pctExplicita + '%' }"></div>
                  </div>
                </div>
                <div>
                  <div class="flex justify-between text-xs text-gray-500 font-semibold mb-1">
                    <span>Información latente</span>
                    <span>{{ pctLatente }}%</span>
                  </div>
                  <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gray-400 rounded-full" :style="{ width: pctLatente + '%' }"></div>
                  </div>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                  <h4 class="text-xs text-gray-400 uppercase font-semibold mb-1.5">Descubierto</h4>
                  <ul class="space-y-1">
                    <li v-for="item in mapa.descubierto" :key="item" class="text-gray-500">
                      <span class="text-green-600 font-bold">✓</span> {{ item }}
                    </li>
                  </ul>
                </div>
                <div>
                  <h4 class="text-xs text-gray-400 uppercase font-semibold mb-1.5">No descubierto</h4>
                  <ul class="space-y-1">
                    <li v-for="item in mapa.no_descubierto" :key="item" class="text-gray-500">
                      <span class="text-red-500 font-bold">✗</span> {{ item }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Competencias -->
            <div class="mb-5">
              <div class="flex justify-between items-center mb-2">
                <label class="text-xs font-semibold text-gray-500">Evaluación por competencias</label>
                <span class="text-xs text-gray-400 italic">Ajusta cada puntuación</span>
              </div>
              <div class="divide-y divide-gray-50">
                <div v-for="(comp, i) in form.competencias" :key="comp.competencia_id"
                  class="flex items-center gap-3 py-2">
                  <span class="flex-1 text-xs text-gray-500">{{ nombreCompetencia(comp.competencia_id) }}</span>
                  <span class="text-xs text-gray-400 italic">IA: {{ borrador_competencias[i]?.puntuacion ?? '—' }}</span>
                  <input v-model.number="comp.puntuacion" type="number" min="0" max="10" step="0.5"
                    class="w-14 text-center text-xs border border-gray-200 rounded px-2 py-1 bg-gray-50 focus:outline-none focus:border-gray-400"/>
                  <span class="text-xs text-gray-400">/10</span>
                </div>
              </div>
            </div>

            <!-- Calificación final -->
            <div class="mb-5">
              <label class="text-xs font-semibold text-gray-500 mb-2 block">Calificación final</label>
              <div class="flex items-center gap-4 bg-gray-50 border-2 border-gray-200 rounded-lg p-3">
                <input v-model.number="form.calificacion" type="number" min="0" max="10" step="0.1"
                  class="w-20 text-center text-2xl font-bold text-gray-600 border-2 border-gray-200 rounded-md py-2 bg-white focus:outline-none focus:border-gray-400"/>
                <div>
                  <div class="text-xs text-gray-400">/ 10</div>
                  <div class="text-xs text-gray-400 mt-0.5">Sugerencia IA: {{ resultado?.resultado?.borrador_calificacion }}</div>
                </div>
              </div>
            </div>

            <!-- Feedback -->
            <div class="mb-5">
              <div class="flex justify-between items-center mb-1.5">
                <label class="text-xs font-semibold text-gray-500">Feedback para el alumno</label>
                <span class="text-xs text-gray-400 italic">Editable</span>
              </div>
              <textarea v-model="form.feedback" rows="4"
                class="w-full text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-md p-3 leading-relaxed resize-none focus:outline-none focus:border-gray-400">
              </textarea>
            </div>

            <!-- Acciones -->
            <div v-if="msgPublicar" :class="['text-xs mb-3 px-3 py-2 rounded-md', msgPublicar.ok ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600']">
              {{ msgPublicar.texto }}
            </div>
            <div class="flex justify-between pt-4 border-t border-gray-100">
              <button @click="$router.back()"
                class="border-2 border-gray-200 text-gray-500 text-sm font-semibold px-5 py-2.5 rounded-md hover:bg-gray-50">
                Volver
              </button>
              <button @click="publicar" :disabled="publicando"
                class="bg-gray-500 hover:bg-gray-600 disabled:opacity-50 text-white text-sm font-semibold px-5 py-2.5 rounded-md">
                {{ publicando ? 'Publicando...' : 'Publicar al alumno →' }}
              </button>
            </div>

          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
/**
 * EmitirCalificacionView — WF-13 — CU-24.
 * Layout 2 columnas: transcripción izq · evaluación der.
 * Referencia: docs/prototipos/13_emitir_calificacion.html
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'

const route = useRoute()
const router = useRouter()
const auth  = useAuthStore()
const resultadoId = Number(route.params.id)

const resultado             = ref(null)
const competencias          = ref([]) // lista completa de competencias del sistema
const borrador_competencias = ref([])
const mapa                  = ref({ descubierto: [], no_descubierto: [] })
const loading               = ref(true)
const error                 = ref('')
const publicando            = ref(false)
const msgPublicar           = ref(null)

const form = ref({
  calificacion:  0,
  feedback:      '',
  resumen:       '',
  competencias:  [],
})

async function cargar() {
  loading.value = true
  error.value   = ''
  try {
    const [resData, compData] = await Promise.all([
      api.get(`/resultados/${resultadoId}`),
      api.get('/competencias'),
    ])
    resultado.value  = resData.data
    competencias.value = compData.data.data ?? []

    const r = resData.data.resultado
    borrador_competencias.value = r.borrador_competencias ?? []
    mapa.value = r.borrador_mapa_descubrimiento ?? { descubierto: [], no_descubierto: [] }

    // Prellenar formulario con borrador IA
    form.value.calificacion = r.final_calificacion ?? r.borrador_calificacion ?? 0
    form.value.feedback     = r.final_feedback     ?? r.borrador_feedback     ?? ''
    form.value.resumen      = r.borrador_resumen   ?? ''
    form.value.competencias = (r.final_competencias ?? r.borrador_competencias ?? []).map(c => ({
      competencia_id: c.competencia_id,
      puntuacion:     c.puntuacion ?? 0,
      comentario:     c.comentario ?? '',
    }))
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Error al cargar la evaluación'
  } finally {
    loading.value = false
  }
}

async function publicar() {
  publicando.value = true
  msgPublicar.value = null
  try {
    await api.post(`/resultados/${resultadoId}/publicar`, {
      final_calificacion: form.value.calificacion,
      final_feedback:     form.value.feedback,
      final_competencias: form.value.competencias,
    })
    msgPublicar.value = { ok: true, texto: 'Calificación publicada correctamente. El alumno ya puede ver su nota.' }
    setTimeout(() => router.back(), 2000)
  } catch (e) {
    msgPublicar.value = { ok: false, texto: e.response?.data?.message ?? 'Error al publicar' }
  } finally {
    publicando.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function nombreCompetencia(id) {
  return competencias.value.find(c => c.id === id)?.nombre ?? `Competencia ${id}`
}

const pctExplicita = computed(() => {
  // Información explícita siempre se descubre (por diseño del agente)
  return 100
})

const pctLatente = computed(() => {
  const desc = mapa.value.descubierto?.length ?? 0
  const total = desc + (mapa.value.no_descubierto?.length ?? 0)
  return total > 0 ? Math.round((desc / total) * 100) : 0
})

function iniciales(name) {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function formatFecha(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('es-ES', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => cargar())
</script>
