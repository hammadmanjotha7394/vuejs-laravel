import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import StoreLayout from '@/layouts/StoreLayout.vue'
import HomeView from '@/views/store/HomeView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import ForbiddenView from '@/views/ForbiddenView.vue'
import NotFoundView from '@/views/NotFoundView.vue'

import CatalogView from '@/views/store/CatalogView.vue'
import ProductDetailView from '@/views/store/ProductDetailView.vue'
import CartView from '@/views/store/CartView.vue'
import WishlistView from '@/views/store/WishlistView.vue'
import CheckoutView from '@/views/store/CheckoutView.vue'
import CheckoutSuccessView from '@/views/store/CheckoutSuccessView.vue'
import CheckoutCancelView from '@/views/store/CheckoutCancelView.vue'

import AccountLayout from '@/views/store/account/AccountLayout.vue'
import AccountOverviewView from '@/views/store/account/AccountOverviewView.vue'
import AccountOrdersView from '@/views/store/account/AccountOrdersView.vue'
import AccountOrderDetailView from '@/views/store/account/AccountOrderDetailView.vue'
import AccountAddressesView from '@/views/store/account/AccountAddressesView.vue'
import AccountProfileView from '@/views/store/account/AccountProfileView.vue'
import AccountWishlistView from '@/views/store/account/AccountWishlistView.vue'
import AccountReviewsView from '@/views/store/account/AccountReviewsView.vue'

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
      {
        path: 'cart',
        name: 'cart',
        component: CartView,
      },
      {
        path: 'wishlist',
        name: 'wishlist',
        component: WishlistView,
      },
      {
        path: 'checkout',
        name: 'checkout',
        component: CheckoutView,
      },
      {
        path: 'checkout/success',
        name: 'checkout.success',
        component: CheckoutSuccessView,
      },
      {
        path: 'checkout/cancel',
        name: 'checkout.cancel',
        component: CheckoutCancelView,
      },
      // Customer Account Area (Protected by requiresAuth)
      {
        path: 'account',
        component: AccountLayout,
        meta: { requiresAuth: true },
        children: [
          {
            path: '',
            name: 'account.overview',
            component: AccountOverviewView,
          },
          {
            path: 'orders',
            name: 'account.orders',
            component: AccountOrdersView,
          },
          {
            path: 'orders/:number',
            name: 'account.order.detail',
            component: AccountOrderDetailView,
          },
          {
            path: 'addresses',
            name: 'account.addresses',
            component: AccountAddressesView,
          },
          {
            path: 'profile',
            name: 'account.profile',
            component: AccountProfileView,
          },
          {
            path: 'wishlist',
            name: 'account.wishlist',
            component: AccountWishlistView,
          },
          {
            path: 'reviews',
            name: 'account.reviews',
            component: AccountReviewsView,
          },
        ],
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

  // Admin Dashboard Routes (Lazy-loaded chunks so they never slow down the storefront)
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresStaff: true },
    children: [
      {
        path: '',
        name: 'admin.dashboard',
        component: () => import('@/views/admin/DashboardView.vue'),
      },
      {
        path: 'orders',
        name: 'admin.orders',
        component: () => import('@/views/admin/OrdersView.vue'),
        meta: { permission: 'orders.view' },
      },
      {
        path: 'orders/:id',
        name: 'admin.orders.detail',
        component: () => import('@/views/admin/OrderDetailView.vue'),
        meta: { permission: 'orders.view' },
      },
      {
        path: 'products',
        name: 'admin.products',
        component: () => import('@/views/admin/ProductsView.vue'),
        meta: { permission: 'products.view' },
      },
      {
        path: 'products/create',
        name: 'admin.products.create',
        component: () => import('@/views/admin/ProductFormView.vue'),
        meta: { permission: 'products.create' },
      },
      {
        path: 'products/:id/edit',
        name: 'admin.products.edit',
        component: () => import('@/views/admin/ProductFormView.vue'),
        meta: { permission: 'products.update' },
      },
      {
        path: 'categories',
        name: 'admin.categories',
        component: () => import('@/views/admin/CategoriesView.vue'),
        meta: { permission: 'categories.manage' },
      },
      {
        path: 'brands',
        name: 'admin.brands',
        component: () => import('@/views/admin/AdminModulePlaceholderView.vue'),
        meta: {
          permission: 'brands.manage',
          title: 'Brands & Labels',
          description: 'Manage product brands, logos, and brand landing descriptions.',
        },
      },
      {
        path: 'customers',
        name: 'admin.customers',
        component: () => import('@/views/admin/CustomersView.vue'),
        meta: { permission: 'users.view' },
      },
      {
        path: 'reviews',
        name: 'admin.reviews',
        component: () => import('@/views/admin/AdminModulePlaceholderView.vue'),
        meta: {
          permission: 'reviews.manage',
          title: 'Product Reviews',
          description: 'Approve or reject customer reviews, create custom storefront reviews, and import via CSV.',
        },
      },
      {
        path: 'coupons',
        name: 'admin.coupons',
        component: () => import('@/views/admin/AdminModulePlaceholderView.vue'),
        meta: {
          permission: 'coupons.manage',
          title: 'Coupons & Discounts',
          description: 'Create and manage promo codes, percentage/fixed discounts, and minimum order rules.',
        },
      },
      {
        path: 'chat',
        name: 'admin.chat',
        component: () => import('@/views/admin/AdminModulePlaceholderView.vue'),
        meta: {
          permission: 'chat.manage',
          title: 'Live Chat Inbox',
          description: 'Respond to real-time customer inquiries, view visitor cart context, and use canned replies.',
        },
      },
      {
        path: 'marketing',
        name: 'admin.marketing',
        component: () => import('@/views/admin/AdminModulePlaceholderView.vue'),
        meta: {
          title: 'Marketing (Hero Slides, Popups & Banners)',
          description: 'Configure homepage hero slides, promotional popups, newsletter signups, and trust banners.',
        },
      },
      {
        path: 'menus',
        name: 'admin.menus',
        component: () => import('@/views/admin/MenusView.vue'),
        meta: { permission: 'menus.manage' },
      },
      {
        path: 'pages',
        name: 'admin.pages',
        component: () => import('@/views/admin/AdminModulePlaceholderView.vue'),
        meta: {
          permission: 'pages.manage',
          title: 'CMS Pages',
          description: 'Edit About, FAQ, Shipping, Returns, Privacy Policy, and Terms of Service pages.',
        },
      },
      {
        path: 'finance',
        name: 'admin.finance',
        component: () => import('@/views/admin/FinanceView.vue'),
        meta: { permission: 'finance.view' },
      },
      {
        path: 'users',
        name: 'admin.users',
        component: () => import('@/views/admin/UsersView.vue'),
        meta: { permission: 'users.view' },
      },
      {
        path: 'roles',
        name: 'admin.roles',
        component: () => import('@/views/admin/RolesView.vue'),
        meta: { permission: 'roles.manage' },
      },
      {
        path: 'settings',
        name: 'admin.settings',
        component: () => import('@/views/admin/SettingsView.vue'),
        meta: { permission: 'settings.view' },
      },
      {
        path: 'email-templates',
        name: 'admin.email-templates',
        component: () => import('@/views/admin/AdminModulePlaceholderView.vue'),
        meta: {
          permission: 'mail.manage',
          title: 'Email Templates & Logs',
          description: 'Customize transactional HTML email templates, preview variables, and inspect SMTP delivery logs.',
        },
      },
      {
        path: 'activity-log',
        name: 'admin.activity-log',
        component: () => import('@/views/admin/AdminModulePlaceholderView.vue'),
        meta: {
          permission: 'activity.view',
          title: 'System Activity Log',
          description: 'Audit trail of administrative actions, actor users, timestamps, and IP addresses.',
        },
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
