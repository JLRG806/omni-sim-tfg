<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-gray-200 border border-dashed border-gray-400 flex items-center justify-center text-xs text-gray-500">Logo</div>
        <span class="font-bold text-gray-800">OmniSim</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Profesor</span>
        <span class="text-sm text-gray-600">{{ auth.user?.name }}</span>
      </div>
    </header>

    <main class="flex-1 max-w-2xl mx-auto w-full p-6">
      <div class="text-xs text-gray-500 mb-4">
        Crear Escenario — Paso 2 de 2
      </div>

      <!-- Stepper -->
      <div class="flex items-center gap-3 mb-6">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-bold">✓</div>
          <span class="text-sm text-green-600">Escenario</span>
        </div>
        <div class="flex-1 h-0.5 bg-blue-400" />
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold">2</div>
          <span class="text-sm font-semibold text-blue-600">Perfil del agente</span>
        </div>
      </div>

      <div v-if="loadingCompetencias" class="text-center py-12 text-gray-400 text-sm">Cargando competencias…</div>

      <div v-else class="bg-white border border-gray-200 rounded-xl p-7">
        <h1 class="text-xl font-bold text-gray-800 mb-6">Perfil del Agente</h1>

        <div class="mb-5">
          <label class="block text-xs font-semibold text-gray-600 mb-1">¿Quién es el personaje? (rol/identidad)</label>
          <input v-model="form.rol_identidad" type="text" placeholder="Ej. Paciente adulto con trastorno de ansiedad"
            class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
            :class="errors.rol_identidad ? 'border-red-400' : 'border-gray-200'" />
          <p v-if="errors.rol_identidad" class="text-xs text-red-500 mt-1">{{ errors.rol_identidad[0] }}</p>
        </div>

        <div class="mb-5">
          <label class="block text-xs font-semibold text-gray-600 mb-1">¿Cuál es su historia? (trasfondo)</label>
          <textarea v-model="form.trasfondo" rows="3" placeholder="Trabaja como contable, sufre ansiedad desde hace 2 años…"
            class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400 resize-none"
            :class="errors.trasfondo ? 'border-red-400' : 'border-gray-200'" />
          <p v-if="errors.trasfondo" class="text-xs text-red-500 mt-1">{{ errors.trasfondo[0] }}</p>
        </div>

        <div class="mb-5">
          <label class="block text-xs font-semibold text-gray-600 mb-1">¿Qué sabe y qué no sabe? (conocimientos)</label>
          <textarea v-model="form.conocimientos" rows="2" placeholder="Conoce su diagnóstico pero no los tratamientos…"
            class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400 resize-none"
            :class="errors.conocimientos ? 'border-red-400' : 'border-gray-200'" />
        </div>

        <div class="mb-5">
          <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-semibold text-gray-600">Primer mensaje (bienvenida)</label>
            <button type="button" disabled
              class="text-xs border border-gray-200 px-2 py-1 rounded text-gray-300 cursor-not-allowed"
              title="Disponible cuando n8n esté configurado">
              Sugerir con IA (próximamente)
            </button>
          </div>
          <textarea v-model="form.mensaje_bienvenida" rows="2"
            placeholder="Ej: Buenos días, tengo cita con usted..."
            class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400 resize-none"
            :class="errors.mensaje_bienvenida ? 'border-red-400' : 'border-gray-200'" />
          <p class="text-xs text-gray-400 mt-1 italic">Cómo abrirá la conversación el personaje al iniciar la simulación</p>
        </div>

        <div class="mb-5">
          <label class="block text-xs font-semibold text-gray-600 mb-1">¿Cómo se comunica? (comportamiento)</label>
          <textarea v-model="form.comportamiento" rows="2" placeholder="Habla rápido, interrumpe, gesticula con las manos…"
            class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400 resize-none"
            :class="errors.comportamiento ? 'border-red-400' : 'border-gray-200'" />
        </div>

        <div class="mb-5">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Tono emocional</label>
          <select v-model="form.tono_emocional"
            class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400">
            <option value="">Seleccionar…</option>
            <option value="formal">Formal</option>
            <option value="amigable">Amigable</option>
            <option value="empatico">Empático</option>
            <option value="serio">Serio</option>
            <option value="distante">Distante</option>
          </select>
        </div>

        <!-- Restricciones -->
        <div class="mb-5">
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-semibold text-gray-600">Restricciones del personaje</label>
            <button @click="añadir('restricciones')" type="button" class="text-xs text-blue-600 hover:underline">+ Añadir</button>
          </div>
          <div v-for="(item, i) in form.restricciones" :key="'res'+i" class="flex gap-2 mb-2">
            <input v-model="form.restricciones[i]" type="text" :placeholder="`Ej: No revelar el presupuesto sin que se le pregunte`"
              class="flex-1 px-3 py-2 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400" />
            <button v-if="form.restricciones.length > 1" @click="quitar('restricciones', i)" type="button" class="text-gray-400 hover:text-red-500 text-lg px-1">×</button>
          </div>
          <p class="text-xs text-gray-400 italic">Reglas que el personaje no debe romper durante la simulación</p>
        </div>

        <!-- Dificultad — 3 cards clickables -->
        <div class="mb-5">
          <label class="block text-xs font-semibold text-gray-600 mb-2">Dificultad de la entrevista</label>
          <div class="grid grid-cols-3 gap-3">
            <button v-for="op in dificultades" :key="op.valor" type="button"
              @click="form.nivel_dificultad = op.valor"
              :class="['p-3 border-2 rounded-lg text-center transition-colors',
                form.nivel_dificultad === op.valor
                  ? 'border-gray-600 bg-gray-100'
                  : 'border-gray-200 bg-gray-50 hover:border-gray-300']">
              <div class="text-sm font-bold text-gray-700 mb-1">{{ op.label }}</div>
              <div class="text-xs text-gray-400">{{ op.desc }}</div>
            </button>
          </div>
          <p v-if="errors.nivel_dificultad" class="text-xs text-red-500 mt-1">{{ errors.nivel_dificultad[0] }}</p>
        </div>

        <!-- Información explícita -->
        <div class="mb-5">
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-semibold text-gray-600">¿Qué dice abiertamente? (info explícita)</label>
            <button @click="añadir('informacion_explicita')" type="button" class="text-xs text-blue-600 hover:underline">+ Añadir</button>
          </div>
          <div v-for="(item, i) in form.informacion_explicita" :key="'exp'+i" class="flex gap-2 mb-2">
            <input v-model="form.informacion_explicita[i]" type="text" :placeholder="`Info explícita ${i+1}`"
              class="flex-1 px-3 py-2 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400" />
            <button v-if="form.informacion_explicita.length > 1" @click="quitar('informacion_explicita', i)" type="button" class="text-gray-400 hover:text-red-500 text-lg px-1">×</button>
          </div>
        </div>

        <!-- Información latente -->
        <div class="mb-5">
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-semibold text-gray-600">¿Qué oculta o cuesta decir? (info latente)</label>
            <button @click="añadir('informacion_latente')" type="button" class="text-xs text-blue-600 hover:underline">+ Añadir</button>
          </div>
          <div v-for="(item, i) in form.informacion_latente" :key="'lat'+i" class="flex gap-2 mb-2">
            <input v-model="form.informacion_latente[i]" type="text" :placeholder="`Info latente ${i+1}`"
              class="flex-1 px-3 py-2 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400" />
            <button v-if="form.informacion_latente.length > 1" @click="quitar('informacion_latente', i)" type="button" class="text-gray-400 hover:text-red-500 text-lg px-1">×</button>
          </div>
        </div>

        <!-- Criterios de evaluación -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-semibold text-gray-600">¿Cómo evalúas al alumno? (criterios)</label>
            <button @click="añadirCriterio" type="button" class="text-xs text-blue-600 hover:underline">+ Añadir criterio</button>
          </div>
          <div v-for="(c, i) in form.criterios_evaluacion" :key="'crit'+i" class="flex gap-2 mb-2">
            <select v-model="c.competencia_id"
              class="w-40 px-2 py-2 border-2 border-gray-200 rounded-lg text-xs bg-gray-50 focus:outline-none focus:border-blue-400">
              <option value="">Competencia…</option>
              <option v-for="comp in competencias" :key="comp.id" :value="comp.id">{{ comp.nombre }}</option>
            </select>
            <input v-model="c.contenido" type="text" placeholder="Descripción del criterio…"
              class="flex-1 px-3 py-2 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400" />
            <button v-if="form.criterios_evaluacion.length > 1" @click="quitarCriterio(i)" type="button" class="text-gray-400 hover:text-red-500 text-lg px-1">×</button>
          </div>
          <p v-if="errors.criterios_evaluacion" class="text-xs text-red-500 mt-1">{{ errors.criterios_evaluacion[0] }}</p>
        </div>

        <p v-if="errorGeneral" class="text-sm text-red-500 mb-4">{{ errorGeneral }}</p>

        <div class="flex gap-3 pt-5 border-t border-gray-100">
          <button @click="guardar" :disabled="loading"
            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-semibold rounded-lg">
            {{ loading ? 'Guardando…' : 'Finalizar escenario' }}
          </button>
          <router-link v-if="escStore.asignaturaId" :to="`/profesor/asignaturas/${escStore.asignaturaId}`"
            class="px-6 py-2.5 border-2 border-gray-300 text-gray-600 text-sm font-semibold rounded-lg hover:bg-gray-50">
            Cancelar
          </router-link>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
/**
 * CrearEscenarioFase2View — CU-18 Fase 2.
 * Recibe escenario_id de la ruta (:id).
 * Carga competencias disponibles para los criterios de evaluación.
 */
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useEscenarioStore } from '@/stores/escenarioStore'
import api from '@/plugins/axios'

const route    = useRoute()
const router   = useRouter()
const auth     = useAuthStore()
const escStore = useEscenarioStore()

const escenarioId = Number(route.params.id)

const form = ref({
  rol_identidad:        '',
  trasfondo:            '',
  conocimientos:        '',
  mensaje_bienvenida:   '',
  comportamiento:       '',
  tono_emocional:       '',
  nivel_dificultad:     '',
  informacion_explicita: [''],
  informacion_latente:   [''],
  restricciones:         [''],
  criterios_evaluacion:  [{ competencia_id: '', contenido: '' }],
})

const competencias        = ref([])
const loadingCompetencias = ref(true)
const errors              = ref({})
const errorGeneral        = ref('')
const loading             = ref(false)

const dificultades = [
  { valor: 'facil',   label: 'Fácil',   desc: 'Cooperativo, revela info con preguntas básicas' },
  { valor: 'medio',   label: 'Medio',   desc: 'Natural, requiere preguntas de seguimiento' },
  { valor: 'dificil', label: 'Difícil', desc: 'Evasivo, info latente solo con preguntas específicas' },
]

function añadir(campo) {
  form.value[campo].push('')
}
function quitar(campo, i) {
  form.value[campo].splice(i, 1)
}
function añadirCriterio() {
  form.value.criterios_evaluacion.push({ competencia_id: '', contenido: '' })
}
function quitarCriterio(i) {
  form.value.criterios_evaluacion.splice(i, 1)
}

onMounted(async () => {
  try {
    const { data } = await api.get('/competencias')
    competencias.value = data.data

    if (route.query.modo === 'editar') {
      const { data: esc } = await api.get(`/escenarios/${escenarioId}`)
      const p = esc.data?.perfil
      if (p) {
        form.value.rol_identidad         = p.rol_identidad        ?? ''
        form.value.trasfondo             = p.trasfondo            ?? ''
        form.value.conocimientos         = p.conocimientos        ?? ''
        form.value.mensaje_bienvenida    = p.mensaje_bienvenida   ?? ''
        form.value.comportamiento        = p.comportamiento       ?? ''
        form.value.tono_emocional        = p.tono_emocional       ?? ''
        form.value.nivel_dificultad      = p.nivel_dificultad     ?? ''
        form.value.informacion_explicita = p.informacion_explicita?.length ? p.informacion_explicita : ['']
        form.value.informacion_latente   = p.informacion_latente?.length   ? p.informacion_latente   : ['']
        form.value.restricciones         = p.restricciones?.length         ? p.restricciones         : ['']
        form.value.criterios_evaluacion  = p.criterios_evaluacion?.length
          ? p.criterios_evaluacion
          : [{ competencia_id: '', contenido: '' }]
      }
    }
  } catch {
    competencias.value = []
  } finally {
    loadingCompetencias.value = false
  }
})

async function guardar() {
  errors.value       = {}
  errorGeneral.value = ''
  loading.value      = true
  try {
    // POST = crear (CU-18), PUT = editar (CU-19)
    const method = route.query.modo === 'editar' ? 'put' : 'post'
    await api[method](`/escenarios/${escenarioId}/perfil`, {
      ...form.value,
      informacion_explicita: form.value.informacion_explicita.filter(s => s.trim()),
      informacion_latente:   form.value.informacion_latente.filter(s => s.trim()),
      restricciones:         form.value.restricciones.filter(s => s.trim()),
      criterios_evaluacion:  form.value.criterios_evaluacion.filter(c => c.competencia_id && c.contenido.trim()),
    })
    const asignaturaId = escStore.asignaturaId
    escStore.limpiar()
    router.push(asignaturaId
      ? `/profesor/asignaturas/${asignaturaId}`
      : '/profesor/dashboard')
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors ?? {}
    else errorGeneral.value = e.response?.data?.message ?? 'Error al guardar el perfil'
  } finally {
    loading.value = false
  }
}
</script>
