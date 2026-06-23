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

        <ModalConfirmacion
          :visible="modalVisible"
          titulo="Eliminar asignatura"
          :mensaje="`¿Eliminar '${asignaturaAEliminar?.nombre}'? Esta acción no se puede deshacer.`"
          @confirmar="eliminar"
          @cancelar="modalVisible = false"
        />

        <div class="flex items-center justify-between mb-5">
          <h1 class="text-xl font-bold text-gray-800">Gestión de Asignaturas</h1>
          <router-link
            to="/admin/asignaturas/nueva"
            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg"
          >
            + Crear Asignatura
          </router-link>
        </div>

        <!-- Búsqueda (CU-13) -->
        <div class="flex gap-2 mb-5">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre, código o profesor…"
            class="flex-1 px-4 py-2 border-2 border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-blue-400"
            @keyup.enter="buscar"
          />
          <button @click="buscar" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm rounded-lg">
            Buscar
          </button>
        </div>

        <div v-if="loading" class="text-center py-12 text-gray-400 text-sm">Cargando…</div>
        <div v-else-if="error" class="text-center py-12 text-red-500 text-sm">{{ error }}</div>

        <div v-else class="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nombre</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Código</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Profesor</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="asignaturas.length === 0">
                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">
                  No hay asignaturas registradas.
                </td>
              </tr>
              <tr
                v-for="a in asignaturas"
                :key="a.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="px-4 py-3 text-sm text-gray-800 font-medium">{{ a.nombre }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  <span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-xs">{{ a.codigo }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ a.profesor?.name ?? '—' }}</td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <router-link
                      :to="`/admin/asignaturas/${a.id}/editar`"
                      class="text-xs border border-gray-300 px-3 py-1 rounded hover:bg-gray-50 text-gray-600"
                    >
                      Editar
                    </router-link>
                    <button
                      @click="confirmarEliminar(a)"
                      class="text-xs border border-gray-300 px-3 py-1 rounded hover:bg-red-50 text-gray-500"
                    >
                      Eliminar
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </main>
    </div>
  </div>
</template>

<script setup>
/**
 * GestionAsignaturasView — CU-09 Listar Asignaturas + CU-13 Buscar.
 * Acciones Editar (CU-11) y Eliminar (CU-12) se implementan en sus CUs.
 */
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'
import ModalConfirmacion from '@/components/ModalConfirmacion.vue'

const router  = useRouter()
const auth    = useAuthStore()

const asignaturas        = ref([])
const busqueda           = ref('')
const loading            = ref(false)
const error              = ref('')
const modalVisible       = ref(false)
const asignaturaAEliminar = ref(null)

async function cargarAsignaturas(q = '') {
  loading.value = true
  error.value   = ''
  try {
    const params   = q ? { q } : {}
    const { data } = await api.get('/asignaturas', { params })
    asignaturas.value = data.data
  } catch (e) {
    error.value = 'Error al cargar asignaturas'
  } finally {
    loading.value = false
  }
}

function buscar() {
  cargarAsignaturas(busqueda.value)
}

function confirmarEliminar(asignatura) {
  asignaturaAEliminar.value = asignatura
  modalVisible.value        = true
}

async function eliminar() {
  modalVisible.value = false
  try {
    await api.delete(`/asignaturas/${asignaturaAEliminar.value.id}`)
    asignaturas.value = asignaturas.value.filter(a => a.id !== asignaturaAEliminar.value.id)
  } catch (e) {
    error.value = 'Error al eliminar la asignatura'
  } finally {
    asignaturaAEliminar.value = null
  }
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

onMounted(() => cargarAsignaturas())
</script>
