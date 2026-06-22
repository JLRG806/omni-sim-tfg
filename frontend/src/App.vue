<template>
  <RouterView />
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * Componente raíz de la SPA.
 * Al montar la app restaura el token Sanctum desde localStorage para que
 * las peticiones Axios lleven la cabecera Authorization desde el primer render.
 */
const auth = useAuthStore()
onMounted(() => auth.restaurarToken())
</script>
