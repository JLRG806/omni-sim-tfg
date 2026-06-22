import axios from 'axios'

/**
 * Instancia de Axios preconfigurada para la API de OmniSim.
 *
 * baseURL '/api/v1' funciona tanto en desarrollo (Nginx proxifica en :80)
 * como en producción, sin necesidad de cambiar variables de entorno.
 *
 * withCredentials: true es necesario para que Sanctum pueda leer la cookie
 * de sesión cuando se use autenticación stateful (SPA en mismo origen).
 */
const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
})

/**
 * Interceptor de respuesta: redirige al login en caso de 401 Unauthorized.
 * Evita que cada componente gestione el caso de sesión expirada individualmente.
 */
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // No redirigir en el endpoint de login — el 401 ahí significa credenciales
    // inválidas y debe mostrarse al usuario, no causar una redirección.
    const isAuthEndpoint = error.config?.url?.includes('/auth/')
    if (error.response?.status === 401 && !isAuthEndpoint) {
      window.location.href = '/login'
    }
    return Promise.reject(error)
  },
)

export default api
