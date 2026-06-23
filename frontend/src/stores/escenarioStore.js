import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * Store de escenario. Persiste el escenario_id entre la Fase 1 y la Fase 2
 * de la creación/edición de escenarios (CU-18/19).
 */
export const useEscenarioStore = defineStore('escenario', () => {
  /** @type {import('vue').Ref<number|null>} */
  const escenarioId = ref(null)

  /** @type {import('vue').Ref<number|null>} */
  const asignaturaId = ref(null)

  /**
   * Guarda el contexto de la fase 1 para usarlo en fase 2.
   *
   * @param {number} eId  ID del escenario recién creado
   * @param {number} aId  ID de la asignatura a la que pertenece
   */
  function setEscenario(eId, aId) {
    escenarioId.value  = eId
    asignaturaId.value = aId
  }

  /** Limpia el store tras completar el flujo. */
  function limpiar() {
    escenarioId.value  = null
    asignaturaId.value = null
  }

  return { escenarioId, asignaturaId, setEscenario, limpiar }
})
