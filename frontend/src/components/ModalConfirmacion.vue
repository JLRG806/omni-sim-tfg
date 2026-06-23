<template>
  <Teleport to="body">
    <div v-if="visible" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm mx-4">
        <h3 class="text-base font-semibold text-gray-800 mb-2">{{ titulo }}</h3>
        <p class="text-sm text-gray-500 mb-6">{{ mensaje }}</p>
        <div class="flex gap-3 justify-end">
          <button
            @click="$emit('cancelar')"
            class="px-4 py-2 border border-gray-300 text-gray-600 text-sm font-semibold rounded-lg hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button
            @click="$emit('confirmar')"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg"
          >
            {{ textoConfirmar }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
/**
 * ModalConfirmacion — componente reutilizable de confirmación.
 * Usado en CU-07 (eliminar usuario), CU-12 (eliminar asignatura), etc.
 * Emite 'confirmar' o 'cancelar'.
 */
defineProps({
  visible:         { type: Boolean, default: false },
  titulo:          { type: String,  default: '¿Estás seguro?' },
  mensaje:         { type: String,  default: 'Esta acción no se puede deshacer.' },
  textoConfirmar:  { type: String,  default: 'Eliminar' },
})

defineEmits(['confirmar', 'cancelar'])
</script>
