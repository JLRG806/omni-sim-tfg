import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/plugins/axios'

/**
 * Store de autenticación. Gestiona el usuario en sesión, el token Sanctum
 * y las acciones de login/logout transversales a toda la SPA.
 *
 * El token se persiste en localStorage para sobrevivir recargas de página.
 * Al montar la app se restaura automáticamente en la cabecera de Axios.
 */
export const useAuthStore = defineStore('auth', () => {
  /** @type {import('vue').Ref<Object|null>} */
  const user = ref(null)

  /** @type {import('vue').Ref<string|null>} */
  const token = ref(localStorage.getItem('omnisim_token'))

  /** @type {import('vue').ComputedRef<boolean>} */
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  /** @type {import('vue').ComputedRef<string|null>} */
  const rol = computed(() => user.value?.rol ?? null)

  /**
   * Persiste el token en localStorage y lo adjunta a todas las peticiones Axios.
   *
   * @param {string} nuevoToken
   */
  function setToken(nuevoToken) {
    token.value = nuevoToken
    localStorage.setItem('omnisim_token', nuevoToken)
    api.defaults.headers.common['Authorization'] = `Bearer ${nuevoToken}`
  }

  /**
   * Restaura el token desde localStorage al iniciar la app.
   * Se llama una vez en main.js antes de montar la aplicación.
   */
  function restaurarToken() {
    if (token.value) {
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
    }
  }

  /**
   * Elimina el token y el usuario de la sesión activa.
   * No llama al endpoint de logout — eso lo hace logoutController (CU-02).
   */
  function limpiarSesion() {
    user.value = null
    token.value = null
    localStorage.removeItem('omnisim_token')
    delete api.defaults.headers.common['Authorization']
  }

  return {
    user,
    token,
    isAuthenticated,
    rol,
    setToken,
    restaurarToken,
    limpiarSesion,
  }
})
