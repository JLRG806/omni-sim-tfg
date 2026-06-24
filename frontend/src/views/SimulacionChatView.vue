<template>
  <div class="h-screen flex flex-col overflow-hidden bg-gray-100">

    <!-- Top bar minimal -->
    <div class="bg-white border-b-2 border-gray-200 px-6 py-3 flex justify-between items-center flex-shrink-0">
      <div class="flex items-center gap-4">
        <div class="w-11 h-11 rounded-full bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center text-xs text-gray-400 flex-shrink-0">Avatar</div>
        <div>
          <h1 class="text-sm font-semibold text-gray-800">{{ sesion?.escenario?.perfil?.rol_identidad?.split(',')[0] ?? 'Personaje' }}</h1>
          <p class="text-xs text-gray-400">{{ sesion?.escenario?.titulo }} · {{ sesion?.escenario?.descripcion_situacion?.substring(0, 50) }}...</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span class="bg-gray-100 text-gray-500 text-xs px-3 py-1.5 rounded-full">📨 <strong>{{ mensajes.length }}</strong> mensajes</span>
        <span class="bg-gray-100 text-gray-500 text-xs px-3 py-1.5 rounded-full">⏱ <strong>{{ tiempoTranscurrido }}</strong></span>
        <button @click="pausar" :disabled="enviando"
          class="text-xs border border-gray-200 px-3 py-2 rounded-md text-gray-500 hover:bg-gray-50 font-medium disabled:opacity-50">
          Pausar y salir
        </button>
        <button @click="mostrarConfirmFinalizar = true" :disabled="enviando"
          class="text-xs border border-red-200 px-3 py-2 rounded-md text-red-500 hover:bg-red-50 font-semibold disabled:opacity-50">
          Finalizar sesión
        </button>
      </div>
    </div>

    <!-- Main chat area -->
    <div class="flex-1 flex overflow-hidden">

      <!-- Panel izquierdo: contexto -->
      <div class="w-72 bg-white border-r-2 border-gray-200 p-5 overflow-y-auto flex-shrink-0">
        <div class="mb-5">
          <h3 class="text-xs text-gray-400 uppercase font-bold tracking-wide mb-2">Contexto del escenario</h3>
          <p class="text-xs text-gray-500 leading-relaxed">{{ sesion?.escenario?.descripcion_situacion }}</p>
        </div>
        <div class="mb-5">
          <h3 class="text-xs text-gray-400 uppercase font-bold tracking-wide mb-2">Tus objetivos</h3>
          <div v-for="obj in sesion?.escenario?.objetivos" :key="obj.orden"
            class="text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded px-2.5 py-1.5 mb-1.5">
            {{ obj.contenido }}
          </div>
        </div>
        <div>
          <h3 class="text-xs text-gray-400 uppercase font-bold tracking-wide mb-2">Dificultad</h3>
          <span :class="dificultadClass(sesion?.escenario?.perfil?.nivel_dificultad)"
            class="text-xs font-semibold px-2.5 py-1 rounded-full">
            {{ dificultadLabel(sesion?.escenario?.perfil?.nivel_dificultad) }}
          </span>
        </div>
      </div>

      <!-- Área de mensajes -->
      <div class="flex-1 flex flex-col bg-gray-50">
        <div ref="scrollArea" class="flex-1 overflow-y-auto px-8 py-6 space-y-4">

          <div v-for="msg in mensajes" :key="msg.id"
            :class="['flex gap-3', msg.emisor === 'alumno' ? 'flex-row-reverse' : '']">
            <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-500 font-semibold flex-shrink-0">
              {{ msg.emisor === 'alumno' ? iniciales : 'AG' }}
            </div>
            <div :class="['max-w-lg', msg.emisor === 'alumno' ? 'flex flex-col items-end' : '']">
              <div :class="['px-4 py-3 rounded-2xl text-sm leading-relaxed text-gray-700',
                msg.emisor === 'alumno'
                  ? 'bg-gray-300 border border-gray-300 rounded-tr-sm'
                  : 'bg-white border border-gray-200 rounded-tl-sm']">
                {{ msg.contenido }}
              </div>
            </div>
          </div>

          <!-- Indicador "escribiendo..." -->
          <div v-if="enviando" class="flex gap-3">
            <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-500 font-semibold flex-shrink-0">AG</div>
            <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3 flex gap-1.5 items-center">
              <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
              <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
              <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
            </div>
          </div>

        </div>

        <!-- Input -->
        <div class="bg-white border-t-2 border-gray-200 px-6 py-4 flex-shrink-0">
          <div class="flex gap-3 items-end">
            <textarea
              v-model="textInput"
              @keydown.enter.exact.prevent="enviar"
              @keydown.shift.enter="null"
              :disabled="enviando"
              placeholder="Escribe tu mensaje al personaje..."
              rows="2"
              class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-2xl text-sm bg-gray-50 text-gray-600 resize-none focus:outline-none focus:border-gray-400 disabled:opacity-50"
            ></textarea>
            <button @click="enviar" :disabled="enviando || !textInput.trim()"
              class="w-11 h-11 bg-gray-500 hover:bg-gray-600 disabled:opacity-40 rounded-full flex items-center justify-center text-white text-lg font-bold flex-shrink-0 transition-colors">
              →
            </button>
          </div>
          <p class="text-xs text-gray-400 text-center mt-2">Pulsa Enter para enviar · Shift+Enter para nueva línea</p>
        </div>
      </div>
    </div>

    <!-- Modal confirmación finalizar -->
    <div v-if="mostrarConfirmFinalizar"
      class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full mx-4 shadow-xl">
        <h2 class="text-base font-semibold text-gray-800 mb-2">¿Finalizar la simulación?</h2>
        <p class="text-sm text-gray-500 mb-5">No podrás enviar más mensajes. La IA generará tu evaluación en unos minutos.</p>
        <div class="flex gap-3 justify-end">
          <button @click="mostrarConfirmFinalizar = false"
            class="border border-gray-200 text-gray-500 text-sm px-4 py-2 rounded-md hover:bg-gray-50">Cancelar</button>
          <button @click="finalizar"
            class="bg-red-500 hover:bg-red-600 text-white text-sm font-semibold px-4 py-2 rounded-md">
            Sí, finalizar
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
/**
 * SimulacionChatView — WF-16 — CU-28 (enviar mensaje) + CU-29 (finalizar) + CU-31 (pausar).
 * Layout: top bar + panel contexto izquierdo + área de chat central.
 * Referencia: docs/prototipos/16_simulacion_chat.html
 */
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useSesionStore } from '@/stores/sesionStore'
import api from '@/plugins/axios'

const route       = useRoute()
const router      = useRouter()
const auth        = useAuthStore()
const sesionStore = useSesionStore()
const sesionId    = Number(route.params.sesionId)

const sesion                = ref(null)
const mensajes              = ref([])
const textInput             = ref('')
const enviando              = ref(false)
const mostrarConfirmFinalizar = ref(false)
const scrollArea            = ref(null)
const tiempoTranscurrido    = ref('00:00')
let   timerInterval         = null

const iniciales = computed(() => {
  const n = auth.user?.name ?? 'Yo'
  return n.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
})

// ── Carga inicial ─────────────────────────────────────────────────────────────
async function cargar() {
  // Si hay datos en el store (navegación normal), usarlos
  if (sesionStore.sesionId === sesionId && sesionStore.mensajes.length > 0) {
    mensajes.value = [...sesionStore.mensajes]
  }
  // Siempre cargar la sesión completa (escenario, objetivos, perfil)
  try {
    const { data } = await api.get(`/sesiones/${sesionId}`)
    sesion.value  = data.sesion
    mensajes.value = data.sesion.mensajes
    sesionStore.setSesion(data.sesion)
    iniciarTimer()
  } catch (e) {
    router.push('/alumno/dashboard')
  }
}

function iniciarTimer() {
  if (!sesion.value?.inicio_at) return
  const inicio = new Date(sesion.value.inicio_at)
  timerInterval = setInterval(() => {
    const seg = Math.floor((Date.now() - inicio) / 1000)
    const m   = Math.floor(seg / 60).toString().padStart(2, '0')
    const s   = (seg % 60).toString().padStart(2, '0')
    tiempoTranscurrido.value = seg >= 3600
      ? `${Math.floor(seg/3600)}:${m}:${s}`
      : `${m}:${s}`
  }, 1000)
}

// ── Enviar mensaje ────────────────────────────────────────────────────────────
async function enviar() {
  const texto = textInput.value.trim()
  if (!texto || enviando.value) return
  textInput.value = ''
  enviando.value  = true

  try {
    const { data } = await api.post(`/sesiones/${sesionId}/mensajes`, { texto })
    mensajes.value.push(...data.mensajes)
    sesionStore.añadirMensajes(data.mensajes)
    await scrollBottom()
  } catch (e) {
    textInput.value = texto
    alert(e.response?.data?.message ?? 'Error al enviar el mensaje')
  } finally {
    enviando.value = false
    await nextTick()
    await scrollBottom()
  }
}

// ── Pausar ────────────────────────────────────────────────────────────────────
async function pausar() {
  try {
    await api.patch(`/sesiones/${sesionId}/pausar`)
    sesionStore.limpiar()
    router.push('/alumno/dashboard')
  } catch (e) {
    alert(e.response?.data?.message ?? 'Error al pausar')
  }
}

// ── Finalizar ─────────────────────────────────────────────────────────────────
async function finalizar() {
  mostrarConfirmFinalizar.value = false
  try {
    await api.patch(`/sesiones/${sesionId}/finalizar`)
    sesionStore.limpiar()
    router.push('/alumno/dashboard')
  } catch (e) {
    alert(e.response?.data?.message ?? 'Error al finalizar')
  }
}

// ── Scroll automático ─────────────────────────────────────────────────────────
async function scrollBottom() {
  await nextTick()
  if (scrollArea.value) {
    scrollArea.value.scrollTop = scrollArea.value.scrollHeight
  }
}

watch(mensajes, () => scrollBottom(), { deep: true })

function dificultadLabel(d) {
  return { facil: 'Fácil', medio: 'Medio', dificil: 'Difícil' }[d] ?? '—'
}
function dificultadClass(d) {
  const c = { facil: 'bg-green-100 text-green-700', medio: 'bg-amber-100 text-amber-700', dificil: 'bg-red-100 text-red-700' }
  return c[d] ?? 'bg-gray-100 text-gray-500'
}

onMounted(() => cargar())
onUnmounted(() => clearInterval(timerInterval))
</script>
