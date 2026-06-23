<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-gray-200 border border-dashed border-gray-400 flex items-center justify-center text-xs text-gray-500">
          Logo
        </div>
        <span class="font-bold text-gray-800">OmniSim</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Administrador</span>
        <span class="text-sm text-gray-600">{{ auth.user?.name }}</span>
        <button @click="handleLogout" class="text-sm border border-gray-300 px-3 py-1 rounded hover:bg-gray-50">
          Cerrar sesión
        </button>
      </div>
    </header>

    <div class="flex flex-1">

      <!-- Sidebar -->
      <aside class="w-52 bg-white border-r border-gray-200 py-4">
        <nav>
          <router-link
            to="/admin/usuarios"
            class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-800 bg-gray-100 border-l-4 border-gray-600"
          >
            <div class="w-4 h-4 bg-gray-300 rounded-sm" /> Usuarios
          </router-link>
          <router-link
            to="/admin/asignaturas"
            class="flex items-center gap-2 px-5 py-2.5 text-sm text-gray-500 hover:bg-gray-50"
          >
            <div class="w-4 h-4 bg-gray-300 rounded-sm" /> Asignaturas
          </router-link>
        </nav>
      </aside>

      <!-- Contenido principal -->
      <main class="flex-1 p-6">

        <div class="flex items-center justify-between mb-5">
          <h1 class="text-xl font-bold text-gray-800">Gestión de Usuarios</h1>
          <router-link
            to="/admin/usuarios/nuevo"
            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg"
          >
            + Nuevo Usuario
          </router-link>
        </div>

        <!-- Barra de búsqueda (CU-08) -->
        <div class="flex gap-2 mb-5">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre, correo o rol…"
            class="flex-1 px-4 py-2 border-2 border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-blue-400"
            @keyup.enter="buscar"
          />
          <button
            @click="buscar"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm rounded-lg"
          >
            Buscar
          </button>
        </div>

        <!-- Estado de carga -->
        <div v-if="loading" class="text-center py-12 text-gray-400 text-sm">Cargando…</div>
        <div v-else-if="error" class="text-center py-12 text-red-500 text-sm">{{ error }}</div>

        <!-- Tabla -->
        <div v-else class="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nombre</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Correo</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rol</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="usuarios.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">
                  No hay usuarios registrados.
                </td>
              </tr>
              <tr
                v-for="usuario in usuarios"
                :key="usuario.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="px-4 py-3 text-sm text-gray-800">{{ usuario.name }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ usuario.email }}</td>
                <td class="px-4 py-3">
                  <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 capitalize">
                    {{ usuario.rol }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span
                    class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold"
                    :class="usuario.estado === 'activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                  >
                    {{ usuario.estado }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <router-link
                      :to="`/admin/usuarios/${usuario.id}/editar`"
                      class="text-xs border border-gray-300 px-3 py-1 rounded hover:bg-gray-50 text-gray-600"
                    >
                      Editar
                    </router-link>
                    <button
                      @click="confirmarEliminar(usuario)"
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
 * GestionUsuariosView — CU-04 Listar Usuarios + CU-08 Buscar Usuario.
 * Punto de entrada del módulo de administración de usuarios.
 * Las acciones Editar (CU-06) y Eliminar (CU-07) se implementan en sus CUs.
 */
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'

const router  = useRouter()
const auth    = useAuthStore()

const usuarios = ref([])
const busqueda = ref('')
const loading  = ref(false)
const error    = ref('')

async function cargarUsuarios(q = '') {
  loading.value = true
  error.value   = ''
  try {
    const params   = q ? { q } : {}
    const { data } = await api.get('/usuarios', { params })
    usuarios.value = data.data
  } catch (e) {
    error.value = 'Error al cargar usuarios'
  } finally {
    loading.value = false
  }
}

function buscar() {
  cargarUsuarios(busqueda.value)
}

function confirmarEliminar(usuario) {
  // CU-07 — implementación pendiente
  alert(`Eliminar ${usuario.name} — CU-07 pendiente`)
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

onMounted(() => cargarUsuarios())
</script>
