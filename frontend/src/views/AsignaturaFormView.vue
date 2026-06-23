<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-gray-200 border border-dashed border-gray-400 flex items-center justify-center text-xs text-gray-500">Logo</div>
        <span class="font-bold text-gray-800">OmniSim</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Administrador</span>
        <span class="text-sm text-gray-600">{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-sm border border-gray-300 px-3 py-1 rounded hover:bg-gray-50">Cerrar sesión</button>
      </div>
    </header>

    <div class="flex flex-1">

      <!-- Sidebar -->
      <aside class="w-52 bg-white border-r border-gray-200 py-4">
        <nav>
          <router-link to="/admin/usuarios" class="flex items-center gap-2 px-5 py-2.5 text-sm text-gray-500 hover:bg-gray-50">
            <div class="w-4 h-4 bg-gray-300 rounded-sm" /> Usuarios
          </router-link>
          <router-link to="/admin/asignaturas" class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-800 bg-gray-100 border-l-4 border-gray-600">
            <div class="w-4 h-4 bg-gray-300 rounded-sm" /> Asignaturas
          </router-link>
        </nav>
      </aside>

      <!-- Contenido -->
      <main class="flex-1 p-6">

        <div class="text-xs text-gray-500 mb-4">
          <router-link to="/admin/asignaturas" class="underline hover:text-gray-700">Gestión de Asignaturas</router-link>
          / {{ esEdicion ? 'Editar Asignatura' : 'Crear Asignatura' }}
        </div>

        <h1 class="text-xl font-bold text-gray-800 mb-6">
          {{ esEdicion ? 'Editar Asignatura' : 'Crear Asignatura' }}
        </h1>

        <div class="bg-white border border-gray-200 rounded-xl p-7 max-w-xl">

          <div class="flex gap-4 mb-5">
            <div class="flex-1">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Código</label>
              <input v-model="form.codigo" type="text" placeholder="INF-301"
                class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                :class="errors.codigo ? 'border-red-400' : 'border-gray-200'" />
              <p v-if="errors.codigo" class="text-xs text-red-500 mt-1">{{ errors.codigo[0] }}</p>
            </div>
            <div class="flex-1">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre</label>
              <input v-model="form.nombre" type="text" placeholder="Ingeniería de Requisitos"
                class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                :class="errors.nombre ? 'border-red-400' : 'border-gray-200'" />
              <p v-if="errors.nombre" class="text-xs text-red-500 mt-1">{{ errors.nombre[0] }}</p>
            </div>
          </div>

          <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Descripción</label>
            <textarea v-model="form.descripcion" rows="3" placeholder="Descripción de la asignatura…"
              class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400 resize-none"
              :class="errors.descripcion ? 'border-red-400' : 'border-gray-200'" />
            <p v-if="errors.descripcion" class="text-xs text-red-500 mt-1">{{ errors.descripcion[0] }}</p>
          </div>

          <div class="mb-6">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Profesor asignado</label>
            <select v-model="form.profesor_id"
              class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
              :class="errors.profesor_id ? 'border-red-400' : 'border-gray-200'">
              <option value="">Seleccionar profesor…</option>
              <option v-for="p in profesores" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
            <p v-if="errors.profesor_id" class="text-xs text-red-500 mt-1">{{ errors.profesor_id[0] }}</p>
          </div>

          <p v-if="errorGeneral" class="text-sm text-red-500 mb-4">{{ errorGeneral }}</p>

          <div class="flex gap-3 pt-5 border-t border-gray-100">
            <button @click="guardar" :disabled="loading"
              class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-semibold rounded-lg transition-colors">
              {{ loading ? 'Guardando…' : 'Guardar Asignatura' }}
            </button>
            <router-link to="/admin/asignaturas"
              class="px-6 py-2.5 border-2 border-gray-300 text-gray-600 text-sm font-semibold rounded-lg hover:bg-gray-50">
              Cancelar
            </router-link>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
/**
 * AsignaturaFormView — CU-10 Crear Asignatura + CU-11 Modificar Asignatura.
 * Modo crear: /admin/asignaturas/nueva
 * Modo editar: /admin/asignaturas/:id/editar
 */
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()

const esEdicion = computed(() => !!route.params.id)

const form = ref({ codigo: '', nombre: '', descripcion: '', profesor_id: '' })
const profesores   = ref([])
const errors       = ref({})
const errorGeneral = ref('')
const loading      = ref(false)

async function init() {
  form.value    = { codigo: '', nombre: '', descripcion: '', profesor_id: '' }
  errors.value  = {}
  errorGeneral.value = ''

  try {
    const { data } = await api.get('/usuarios')
    profesores.value = data.data.filter(u => u.rol === 'profesor')
  } catch { /* interceptor handles 401/403 */ }

  if (esEdicion.value) {
    try {
      const { data } = await api.get(`/asignaturas/${route.params.id}`)
      const a = data.data
      form.value = {
        codigo:      a.codigo,
        nombre:      a.nombre,
        descripcion: a.descripcion,
        profesor_id: a.profesor?.id ?? '',
      }
    } catch (e) {
      errorGeneral.value = 'No se pudo cargar la asignatura'
    }
  }
}

watch(() => route.params.id, () => init(), { immediate: true })

async function guardar() {
  errors.value       = {}
  errorGeneral.value = ''
  loading.value      = true
  try {
    const payload = { ...form.value }
    if (esEdicion.value) {
      await api.put(`/asignaturas/${route.params.id}`, payload)
    } else {
      await api.post('/asignaturas', payload)
    }
    router.push('/admin/asignaturas')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      errorGeneral.value = e.response?.data?.message ?? 'Error al guardar la asignatura'
    }
  } finally {
    loading.value = false
  }
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
