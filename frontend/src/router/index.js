import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

/**
 * Router principal de la SPA.
 * La propiedad `meta.requiresAuth` en una ruta activa el guard de navegación.
 * La propiedad `meta.roles` restringe el acceso a roles específicos.
 */
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ── Rutas públicas ────────────────────────────────────────────────────────
    {
      path: '/',
      redirect: '/login',
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
    },

    // ── Rutas protegidas (se añaden por CU en días sucesivos) ─────────────────
    // Ejemplo futuro:
    // {
    //   path: '/dashboard',
    //   name: 'dashboard',
    //   component: () => import('@/views/DashboardView.vue'),
    //   meta: { requiresAuth: true },
    // },
  ],
})

/**
 * Guard global: redirige al login si la ruta requiere autenticación
 * y el usuario no tiene token activo.
 */
router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }
})

export default router
