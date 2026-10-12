import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import HomeView from '@/views/store/HomeView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import ForbiddenView from '@/views/ForbiddenView.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import DashboardView from '@/views/admin/DashboardView.vue'
import UsersView from '@/views/admin/UsersView.vue'
import RolesView from '@/views/admin/RolesView.vue'
import FinanceView from '@/views/admin/FinanceView.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { guestOnly: true },
  },
  {
    path: '/403',
    name: 'forbidden',
    component: ForbiddenView,
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresStaff: true },
    children: [
      {
        path: '',
        name: 'admin.dashboard',
        component: DashboardView,
      },
      {
        path: 'users',
        name: 'admin.users',
        component: UsersView,
        meta: { permission: 'users.view' },
      },
      {
        path: 'roles',
        name: 'admin.roles',
        component: RolesView,
        meta: { permission: 'roles.manage' },
      },
      {
        path: 'finance',
        name: 'admin.finance',
        component: FinanceView,
        meta: { permission: 'finance.view' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Ensure current user session is fetched once on application startup
  if (!authStore.authChecked) {
    await authStore.fetchMe()
  }

  // 1. Guest-only routes (login, register)
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    if (authStore.isStaff) {
      return next('/admin')
    }
    return next('/')
  }

  // 2. Auth required routes
  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (!authStore.isAuthenticated) {
      return next({ path: '/login', query: { redirect: to.fullPath } })
    }

    // Must be staff member for /admin routes
    if (to.matched.some((record) => record.meta.requiresStaff) && !authStore.isStaff) {
      return next('/')
    }

    // Permission requirement
    const requiredPermission = to.meta.permission
    if (requiredPermission && !authStore.hasPermission(requiredPermission)) {
      return next('/403')
    }
  }

  next()
})

export default router
