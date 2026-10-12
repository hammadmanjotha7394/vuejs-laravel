<script setup>
import { computed } from 'vue'
import { useRouter, RouterLink, useRoute } from 'vue-router'
import {
  LayoutDashboard,
  Users,
  Shield,
  CircleDollarSign,
  Settings as SettingsIcon,
  FolderTree,
  Compass,
  Package,
  LogOut,
  Store,
  Menu,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const navItems = computed(() => {
  const items = [
    {
      label: 'Dashboard',
      to: '/admin',
      icon: LayoutDashboard,
      visible: true,
    },
    {
      label: 'Staff & Users',
      to: '/admin/users',
      icon: Users,
      visible: authStore.hasPermission('users.view'),
    },
    {
      label: 'Roles & Permissions',
      to: '/admin/roles',
      icon: Shield,
      visible: authStore.hasPermission('roles.manage'),
    },
    {
      label: 'Products',
      to: '/admin/products',
      icon: Package,
      visible: authStore.hasPermission('products.view'),
    },
    {
      label: 'Categories',
      to: '/admin/categories',
      icon: FolderTree,
      visible: authStore.hasPermission('categories.manage'),
    },
    {
      label: 'Mega Menus',
      to: '/admin/menus',
      icon: Compass,
      visible: authStore.hasPermission('menus.manage'),
    },
    {
      label: 'Finance Reports',
      to: '/admin/finance',
      icon: CircleDollarSign,
      visible: authStore.hasPermission('finance.view'),
    },
    {
      label: 'Store Settings',
      to: '/admin/settings',
      icon: SettingsIcon,
      visible: authStore.hasPermission('settings.view'),
    },
  ]
  return items.filter((item) => item.visible)
})

const activeRoleName = computed(() => {
  return authStore.roles[0] || 'staff'
})
</script>

<template>
  <div class="min-h-screen bg-surface flex flex-col text-ink">
    <!-- Admin Header -->
    <header class="bg-page border-b border-line sticky top-0 z-30">
      <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <RouterLink to="/admin" class="flex items-center gap-2 font-bold tracking-tight text-lg text-ink">
            <span class="w-8 h-8 rounded-full bg-brand text-white flex items-center justify-center text-xs font-semibold">B</span>
            <span>Boutique Admin</span>
          </RouterLink>
          <span class="text-xs px-2.5 py-0.5 rounded-pill bg-brand/10 text-brand font-medium capitalize">
            {{ activeRoleName.replace('_', ' ') }}
          </span>
        </div>

        <div class="flex items-center gap-4">
          <RouterLink
            to="/"
            target="_blank"
            class="hidden sm:inline-flex items-center gap-1.5 text-xs text-ink-muted hover:text-ink font-medium transition"
          >
            <Store class="w-4 h-4" />
            <span>Storefront</span>
          </RouterLink>

          <div class="h-4 w-px bg-line hidden sm:block"></div>

          <div class="flex items-center gap-3 text-sm">
            <div class="text-right hidden sm:block">
              <div class="font-semibold text-xs leading-tight text-ink">{{ authStore.user?.name }}</div>
              <div class="text-[11px] text-ink-muted leading-tight truncate max-w-[140px]">{{ authStore.user?.email }}</div>
            </div>

            <button
              @click="handleLogout"
              class="inline-flex items-center gap-1 px-3 py-1.5 rounded-pill text-xs font-medium text-ink-muted hover:text-error hover:bg-red-50 border border-line transition"
              title="Sign out"
            >
              <LogOut class="w-3.5 h-3.5" />
              <span class="hidden sm:inline">Sign out</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <div class="flex-1 flex max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 gap-8">
      <!-- Sidebar Navigation -->
      <aside class="w-64 flex-shrink-0 hidden md:block">
        <div class="bg-page border border-line rounded-card p-3 shadow-soft sticky top-24">
          <div class="text-[11px] uppercase tracking-wider font-semibold text-ink-muted px-3 py-2">
            Navigation
          </div>

          <nav class="space-y-1 mt-1">
            <RouterLink
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-input font-medium transition"
              :class="[
                route.path === item.to
                  ? 'bg-brand text-white shadow-sm'
                  : 'text-ink-muted hover:text-ink hover:bg-surface'
              ]"
            >
              <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
              <span>{{ item.label }}</span>
            </RouterLink>
          </nav>
        </div>
      </aside>

      <!-- Main Content View -->
      <main class="flex-1 min-w-0">
        <RouterView />
      </main>
    </div>
  </div>
</template>
