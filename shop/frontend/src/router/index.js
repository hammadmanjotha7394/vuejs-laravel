import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import StoreLayout from '@/layouts/StoreLayout.vue'
import HomeView from '@/views/store/HomeView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import ForbiddenView from '@/views/ForbiddenView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import DashboardView from '@/views/admin/DashboardView.vue'
import UsersView from '@/views/admin/UsersView.vue'
import RolesView from '@/views/admin/RolesView.vue'
import FinanceView from '@/views/admin/FinanceView.vue'
import SettingsView from '@/views/admin/SettingsView.vue'
import CategoriesView from '@/views/admin/CategoriesView.vue'
import MenusView from '@/views/admin/MenusView.vue'
import ProductsView from '@/views/admin/ProductsView.vue'
import ProductFormView from '@/views/admin/ProductFormView.vue'

import CatalogView from '@/views/store/CatalogView.vue'
import ProductDetailView from '@/views/store/ProductDetailView.vue'

const routes = [
  // Storefront Layout Routes
  {
    path: '/',
    component: StoreLayout,
    children: [
      {
        path: '',
        name: 'home',
        component: HomeView,
      },
      {
        path: 'shop',
        name: 'shop',
        component: CatalogView,
      },
      {
        path: 'category/:slug',
        name: 'category',
        component: CatalogView,
      },
      {
        path: 'search',
        name: 'search',
        component: CatalogView,
      },
      {
        path: 'sale',
        name: 'sale',
        component: CatalogView,
      },
      {
        path: 'new',
        name: 'new',
        component: CatalogView,
      },
      {
        path: 'products/:slug',
        name: 'product.detail',
        component: ProductDetailView,
      },
      // 404 Catch-All inside Storefront Shell
      {
        path: ':pathMatch(.*)*',
        name: 'not-found',
        component: NotFoundView,
      },
    ],
  },

  // Standalone Auth & System Routes
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

  // Admin Dashboard Routes
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
      {
        path: 'products',
        name: 'admin.products',
        component: ProductsView,
        meta: { permission: 'products.view' },
      },
      {
        path: 'products/create',
        name: 'admin.products.create',
        component: ProductFormView,
        meta: { permission: 'products.create' },
      },
      {
        path: 'products/:id/edit',
        name: 'admin.products.edit',
        component: ProductFormView,
        meta: { permission: 'products.update' },
      },
      {
        path: 'categories',
        name: 'admin.categories',
        component: CategoriesView,
        meta: { permission: 'categories.manage' },
      },
      {
        path: 'menus',
        name: 'admin.menus',
        component: MenusView,
        meta: { permission: 'menus.manage' },
      },
      {
        path: 'settings',
        name: 'admin.settings',
        component: SettingsView,
        meta: { permission: 'settings.view' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
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
