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

    // ── Admin ─────────────────────────────────────────────────────────────────
    {
      path: '/admin/usuarios',
      name: 'gestionUsuarios',
      component: () => import('@/views/GestionUsuariosView.vue'),
      meta: { requiresAuth: true, roles: ['admin'] },
    },

    // ── Profesor ──────────────────────────────────────────────────────────────
    {
      path: '/profesor/dashboard',
      name: 'dashboardProfesor',
      component: () => import('@/views/DashboardProfesorView.vue'),
      meta: { requiresAuth: true, roles: ['profesor'] },
    },

    // ── Alumno ────────────────────────────────────────────────────────────────
    {
      path: '/alumno/dashboard',
      name: 'dashboardAlumno',
      component: () => import('@/views/DashboardAlumnoView.vue'),
      meta: { requiresAuth: true, roles: ['alumno'] },
    },
  ],
})

/**
 * Guard global: redirige al login si la ruta requiere autenticación
 * o si el rol del usuario no está permitido en la ruta.
 */
const rolRedirect = {
  admin:    '/admin/usuarios',
  profesor: '/profesor/dashboard',
  alumno:   '/alumno/dashboard',
}

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.name === 'login' && auth.isAuthenticated) {
    return { path: rolRedirect[auth.user.rol] ?? '/' }
  }
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }
  if (to.meta.roles && !to.meta.roles.includes(auth.user?.rol)) {
    return { name: 'login' }
  }
})

export default router
