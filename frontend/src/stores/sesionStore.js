import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * Store de sesión de simulación.
 * Persiste el sesion_id y los mensajes entre CU-26, CU-27, CU-28 y CU-29.
 */
export const useSesionStore = defineStore('sesion', () => {
  /** @type {import('vue').Ref<number|null>} */
  const sesionId = ref(null)

  /** @type {import('vue').Ref<string>} */
  const estado = ref('')

  /** @type {import('vue').Ref<Array>} */
  const mensajes = ref([])

  /**
   * Inicializa el store con los datos de la sesión recién creada o retomada.
   *
   * @param {object} sesion  Objeto sesión del API
   */
  function setSesion(sesion) {
    sesionId.value = sesion.id
    estado.value   = sesion.estado
    mensajes.value = sesion.mensajes ?? []
  }

  /**
   * Añade uno o más mensajes al historial (sin recargar del servidor).
   *
   * @param {Array} nuevosMensajes
   */
  function añadirMensajes(nuevosMensajes) {
    mensajes.value.push(...nuevosMensajes)
  }

  /** Actualiza el estado de la sesión (e.g. pausada, procesando, finalizada). */
  function setEstado(nuevoEstado) {
    estado.value = nuevoEstado
  }

  /** Limpia el store al salir de la simulación. */
  function limpiar() {
    sesionId.value = null
    estado.value   = ''
    mensajes.value = []
  }

  return { sesionId, estado, mensajes, setSesion, añadirMensajes, setEstado, limpiar }
})
