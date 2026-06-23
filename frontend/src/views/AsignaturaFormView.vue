<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <h1 class="text-xl font-semibold text-gray-800">
      {{ esEdicion ? 'Editar Asignatura' : 'Crear Asignatura' }}
    </h1>
    <p class="text-gray-500 text-sm mt-2">
      Implementación pendiente — CU-10 (crear) / CU-11 (editar)
      · Wireframe: docs/prototipos/06_formulario_asignatura.html
    </p>
    <router-link to="/admin/asignaturas" class="inline-block mt-4 text-sm text-blue-600 underline">
      ← Volver a Asignaturas
    </router-link>
  </div>
</template>

<script setup>
/**
 * AsignaturaFormView — CU-10 Crear Asignatura + CU-11 Modificar Asignatura.
 * Placeholder hasta implementación. Ver wireframe WF-06.
 */
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route     = useRoute()
const esEdicion = computed(() => !!route.params.id)
</script>
