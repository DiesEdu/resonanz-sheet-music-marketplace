import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/marketplace',
      name: 'marketplace',
      component: () => import('../views/MarketplaceView.vue'),
    },
    {
      path: '/cart',
      name: 'cart',
      component: () => import('../views/CartView.vue'),
    },
    {
      path: '/sheet/:id',
      name: 'sheet-detail',
      component: () => import('../views/SheetDetailView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue'),
    },
    {
      path: '/composer/add-sheet',
      name: 'add-sheet',
      component: () => import('../views/AddSheetMusicView.vue'),
      meta: { requiresComposer: true },
    },
    {
      path: '/admin/users',
      name: 'user-management',
      component: () => import('../views/UserManagementView.vue'),
      meta: { requiresAdmin: true },
    },
  ],
})

function getAuthUser() {
  const rawUser = localStorage.getItem('auth_user')
  if (!rawUser) return null

  try {
    return JSON.parse(rawUser)
  } catch {
    return null
  }
}

router.beforeEach((to) => {
  if (!to.meta.requiresAdmin) {
    return true
  }

  const authUser = getAuthUser()
  if (authUser?.role === 'admin') {
    return true
  }

  return { name: 'home' }
})

router.beforeEach((to) => {
  if (!to.meta.requiresComposer) {
    return true
  }

  const authUser = getAuthUser()
  if (authUser?.role === 'composer' || authUser?.role === 'admin') {
    return true
  }

  return { name: 'home' }
})

export default router
