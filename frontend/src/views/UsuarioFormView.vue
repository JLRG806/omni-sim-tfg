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
          <router-link to="/admin/usuarios" class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-800 bg-gray-100 border-l-4 border-gray-600">
            <div class="w-4 h-4 bg-gray-300 rounded-sm" /> Usuarios
          </router-link>
          <router-link to="/admin/asignaturas" class="flex items-center gap-2 px-5 py-2.5 text-sm text-gray-500 hover:bg-gray-50">
            <div class="w-4 h-4 bg-gray-300 rounded-sm" /> Asignaturas
          </router-link>
        </nav>
      </aside>

      <!-- Contenido -->
      <main class="flex-1 p-6">

        <div class="text-xs text-gray-500 mb-4">
          <router-link to="/admin/usuarios" class="underline hover:text-gray-700">Gestión de Usuarios</router-link>
          / {{ esEdicion ? 'Editar Usuario' : 'Crear Usuario' }}
        </div>

        <h1 class="text-xl font-bold text-gray-800 mb-6">
          {{ esEdicion ? 'Editar Usuario' : 'Crear Usuario' }}
        </h1>

        <div class="bg-white border border-gray-200 rounded-xl p-7 max-w-xl">

          <div class="flex gap-4 mb-5">
            <div class="flex-1">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Nombre completo</label>
              <input v-model="form.name" type="text" placeholder="Nombre y apellidos"
                class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                :class="errors.name ? 'border-red-400' : 'border-gray-200'" />
              <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name[0] }}</p>
            </div>
          </div>

          <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-600 mb-1">Correo electrónico</label>
            <input v-model="form.email" type="email" placeholder="usuario@universidad.edu"
              class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
              :class="errors.email ? 'border-red-400' : 'border-gray-200'" />
            <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email[0] }}</p>
          </div>

          <div class="flex gap-4 mb-5">
            <div class="flex-1">
              <label class="block text-xs font-semibold text-gray-600 mb-1">
                {{ esEdicion ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña' }}
              </label>
              <input v-model="form.password" type="password" placeholder="Mínimo 8 caracteres"
                class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                :class="errors.password ? 'border-red-400' : 'border-gray-200'" />
              <p v-if="errors.password" class="text-xs text-red-500 mt-1">{{ errors.password[0] }}</p>
            </div>
            <div class="flex-1">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Confirmar contraseña</label>
              <input v-model="form.password_confirmation" type="password" placeholder="Repite la contraseña"
                class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                :class="errors.password_confirmation ? 'border-red-400' : 'border-gray-200'" />
            </div>
          </div>

          <div class="flex gap-4 mb-6">
            <div class="flex-1">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Rol</label>
              <select v-model="form.rol"
                class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                :class="errors.rol ? 'border-red-400' : 'border-gray-200'">
                <option value="">Seleccionar rol…</option>
                <option value="admin">Administrador</option>
                <option value="profesor">Profesor</option>
                <option value="alumno">Alumno</option>
              </select>
              <p v-if="errors.rol" class="text-xs text-red-500 mt-1">{{ errors.rol[0] }}</p>
            </div>
            <div class="flex-1">
              <label class="block text-xs font-semibold text-gray-600 mb-1">Estado</label>
              <select v-model="form.estado"
                class="w-full px-3 py-2.5 border-2 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-blue-400"
                :class="errors.estado ? 'border-red-400' : 'border-gray-200'">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
              </select>
            </div>
          </div>

          <p v-if="errorGeneral" class="text-sm text-red-500 mb-4">{{ errorGeneral }}</p>

          <div class="flex gap-3 pt-5 border-t border-gray-100">
            <button @click="guardar" :disabled="loading"
              class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-semibold rounded-lg transition-colors">
              {{ loading ? 'Guardando…' : 'Guardar Usuario' }}
            </button>
            <router-link to="/admin/usuarios"
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
 * UsuarioFormView — CU-05 Crear Usuario + CU-06 Modificar Usuario.
 * Modo crear: /admin/usuarios/nuevo (sin :id)
 * Modo editar: /admin/usuarios/:id/editar (con :id)
 */
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import api from '@/plugins/axios'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()

const esEdicion = computed(() => !!route.params.id)

const form = ref({
  name:                  '',
  email:                 '',
  password:              '',
  password_confirmation: '',
  rol:                   '',
  estado:                'activo',
})

const errors       = ref({})
const errorGeneral = ref('')
const loading      = ref(false)

onMounted(async () => {
  if (esEdicion.value) {
    // CU-06: cargar datos del usuario a editar
    try {
      const { data } = await api.get(`/usuarios/${route.params.id}`)
      const u = data.data
      form.value.name   = u.name
      form.value.email  = u.email
      form.value.rol    = u.rol
      form.value.estado = u.estado
    } catch {
      errorGeneral.value = 'No se pudo cargar el usuario'
    }
  }
})

async function guardar() {
  errors.value       = {}
  errorGeneral.value = ''
  loading.value      = true

  try {
    const payload = { ...form.value }
    if (esEdicion.value && !payload.password) {
      delete payload.password
      delete payload.password_confirmation
    }

    if (esEdicion.value) {
      await api.put(`/usuarios/${route.params.id}`, payload)
    } else {
      await api.post('/usuarios', payload)
    }

    router.push('/admin/usuarios')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      errorGeneral.value = e.response?.data?.message ?? 'Error al guardar el usuario'
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
