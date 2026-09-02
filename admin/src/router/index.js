import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/trms/manager',
  },

  // Auth
  {
    path: '/auth',
    name: 'Auth',
    component: () => import('../views/Auth.vue'),
  },

  // TRMS
  {
    path: '/trms',
    children: [
      {
        path: 'manager',
        component: () => import('../views/trms/ManagerDashboard.vue'),
        meta: { roles: ['admin', 'manager'] },
      },
      {
        path: 'concert/audiences',
        component: () => import('../views/trms/ConcertAudiences.vue'),
        meta: { roles: ['admin', 'manager', 'manager_concert'] },
      },
      {
        path: 'concert/invitation-reg',
        component: () => import('../views/trms/InvitationRegistration.vue'),
        meta: { roles: ['admin', 'manager', 'manager_concert'] },
      },
      {
        path: 'concert/scan',
        component: () => import('../views/trms/ConcertScan.vue'),
        meta: { roles: ['admin', 'manager', 'manager_concert'] },
      },
      {
        path: 'concert/ticket/:id',
        component: () => import('../views/trms/TicketPreview.vue'),
        meta: { roles: ['admin', 'manager', 'manager_concert'] },
      },
    ],
  },

  // BMS
  {
    path: '/bms',
    children: [
      {
        path: 'attendance',
        component: () => import('../views/bms/Attendance.vue'),
        meta: { roles: ['admin', 'manager', 'singers_manager'] },
      },
      {
        path: 'manager',
        component: () => import('../views/bms/SingersManagerDashboard.vue'),
        meta: { roles: ['admin', 'singers_manager'] },
      },
    ],
  },

  // Library
  {
    path: '/library',
    children: [
      {
        path: 'composer-dashboard',
        component: () => import('../views/library/ComposerDashboard.vue'),
        meta: { roles: ['composer', 'arranger'] },
      },
      {
        path: 'orders-dashboard',
        component: () => import('../views/library/OrdersDashboard.vue'),
        meta: { roles: ['admin', 'manager', 'manager_scores'] },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// Route guard
router.beforeEach((to, _from) => {
  const requiredRoles = to.meta?.roles
  if (!requiredRoles) return true

  const raw = localStorage.getItem('resonanz-user')
  const user = raw ? JSON.parse(raw) : null
  const userRole = user?.role?.toLowerCase()

  if (!user) {
    return { path: '/auth', query: { redirect: to.fullPath } }
  }

  if (requiredRoles.includes(userRole)) {
    return true
  }

  return { path: '/auth', query: { unauthorized: '1' } }
})

export default router