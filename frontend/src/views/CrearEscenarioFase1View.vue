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
        <router-link :to="`/profesor/asignaturas/${asignaturaId}`" class="underline hover:text-gray-700">Asignatura</router-link>
        / Crear Escenario — Paso 1 de 2
      </div>

      <!-- Stepper -->
      <div class="flex items-center gap-3 mb-6">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold">1</div>
          <span class="text-sm font-semibold text-blue-600">Escenario</span>
        </div>
        <div class="flex-1 h-0.5 bg-gray-200" />
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-gray-300 text-white flex items-center justify-center text-sm font-bold">2</div>
          <span class="text-sm text-gray-400">Perfil del agente</span>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-7">
        <h1 class="text-xl font-bold text-gray-800 mb-6">Crear Escenario</h1>

        <div class="mb-5">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Título del escenario</label>
          <input v-model="form.titulo" type="text" placeholder="Ej. Entrevista con paciente ansioso"
            class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
            :class="errors.titulo ? 'border-red-400' : 'border-gray-200'" />
          <p v-if="errors.titulo" class="text-xs text-red-500 mt-1">{{ errors.titulo[0] }}</p>
        </div>

        <div class="mb-5">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Área de conocimiento (disciplina)</label>
          <input v-model="form.area_conocimiento" type="text" placeholder="Ej. Psicología Clínica"
            class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
            :class="errors.area_conocimiento ? 'border-red-400' : 'border-gray-200'" />
          <p v-if="errors.area_conocimiento" class="text-xs text-red-500 mt-1">{{ errors.area_conocimiento[0] }}</p>
        </div>

        <div class="mb-5">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Describe la situación</label>
          <textarea v-model="form.descripcion_situacion" rows="4"
            placeholder="El paciente llega a consulta mostrando signos visibles de ansiedad…"
            class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400 resize-none"
            :class="errors.descripcion_situacion ? 'border-red-400' : 'border-gray-200'" />
          <p v-if="errors.descripcion_situacion" class="text-xs text-red-500 mt-1">{{ errors.descripcion_situacion[0] }}</p>
        </div>

        <!-- Objetivos de aprendizaje -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-2">
            <label class="block text-xs font-semibold text-gray-600">¿Qué debe aprender el alumno?</label>
            <button @click="añadirObjetivo" type="button" class="text-xs text-blue-600 hover:underline">+ Añadir objetivo</button>
          </div>
          <div v-for="(obj, i) in form.objetivos" :key="i" class="flex gap-2 mb-2">
            <span class="text-xs text-gray-400 pt-2.5 w-5 text-right">{{ i + 1 }}.</span>
            <input v-model="obj.contenido" type="text" :placeholder="`Objetivo ${i + 1}`"
              class="flex-1 px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400" />
            <button v-if="form.objetivos.length > 1" @click="quitarObjetivo(i)" type="button"
              class="text-gray-400 hover:text-red-500 text-lg leading-none px-1">×</button>
          </div>
          <p v-if="errors.objetivos" class="text-xs text-red-500 mt-1">{{ errors.objetivos[0] }}</p>
        </div>

        <p v-if="errorGeneral" class="text-sm text-red-500 mb-4">{{ errorGeneral }}</p>

        <div class="flex gap-3 pt-5 border-t border-gray-100">
          <button @click="siguiente" :disabled="loading"
            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-semibold rounded-lg">
            {{ loading ? 'Guardando…' : 'Siguiente → Perfil del agente' }}
          </button>
          <router-link :to="`/profesor/asignaturas/${asignaturaId}`"
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
 * CrearEscenarioFase1View — CU-18 Fase 1.
 * Recibe asignatura_id via query param (?asignatura_id=X).
 * Al guardar, redirige a CrearEscenarioFase2View con el escenario_id.
 */
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { useEscenarioStore } from '@/stores/escenarioStore'
import api from '@/plugins/axios'

const route    = useRoute()
const router   = useRouter()
const auth     = useAuthStore()
const escStore = useEscenarioStore()

const asignaturaId = ref(Number(route.query.asignatura_id) || null)

const form = ref({
  titulo:                '',
  area_conocimiento:     '',
  descripcion_situacion: '',
  objetivos:             [{ contenido: '', orden: 1 }],
})

const errors       = ref({})
const errorGeneral = ref('')
const loading      = ref(false)

function añadirObjetivo() {
  form.value.objetivos.push({ contenido: '', orden: form.value.objetivos.length + 1 })
}

function quitarObjetivo(i) {
  form.value.objetivos.splice(i, 1)
  form.value.objetivos.forEach((o, idx) => { o.orden = idx + 1 })
}

async function siguiente() {
  errors.value       = {}
  errorGeneral.value = ''
  loading.value      = true
  try {
    const payload = {
      asignatura_id:         asignaturaId.value,
      titulo:                form.value.titulo,
      area_conocimiento:     form.value.area_conocimiento,
      descripcion_situacion: form.value.descripcion_situacion,
      objetivos:             form.value.objetivos.filter(o => o.contenido.trim()),
    }
    const { data } = await api.post('/escenarios', payload)
    escStore.setEscenario(data.escenario_id, asignaturaId.value)
    router.push(`/profesor/escenarios/${data.escenario_id}/perfil`)
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors ?? {}
    else errorGeneral.value = e.response?.data?.message ?? 'Error al guardar el escenario'
  } finally {
    loading.value = false
  }
}
</script>
