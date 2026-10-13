<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import {
  LayoutGrid,
  ShoppingBag,
  MapPin,
  User,
  Heart,
  Star,
  LogOut,
  ShieldCheck,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToastStore()

const navItems = [
  { label: 'Overview', to: '/account', exact: true, icon: LayoutGrid },
  { label: 'My Orders', to: '/account/orders', exact: false, icon: ShoppingBag },
  { label: 'Saved Addresses', to: '/account/addresses', exact: false, icon: MapPin },
  { label: 'Profile & Security', to: '/account/profile', exact: false, icon: User },
  { label: 'Wishlist', to: '/account/wishlist', exact: false, icon: Heart },
  { label: 'My Reviews', to: '/account/reviews', exact: false, icon: Star },
]

const userInitials = computed(() => {
  const name = authStore.user?.name || 'C'
  return name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
})

const isActive = (item) => {
  if (item.exact) {
    return route.path === item.to
  }
  return route.path === item.to || route.path.startsWith(item.to + '/')
}

const handleLogout = async () => {
  await authStore.logout()
  toast.info('You have been signed out.')
  router.push('/login')
}
</script>

<template>
  <div class="max-w-store mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
    <!-- Top Account Header Banner (Hidden on Print) -->
    <div class="print:hidden bg-white rounded-card border border-line p-5 sm:p-6 shadow-card mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-brand/10 text-brand font-bold text-base flex items-center justify-center shrink-0">
          {{ userInitials }}
        </div>
        <div>
          <p class="text-[11px] font-bold uppercase tracking-wider text-brand">Customer Account</p>
          <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">
            {{ authStore.user?.name || 'My Account' }}
          </h1>
          <p class="text-xs text-ink-muted">{{ authStore.user?.email }}</p>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <RouterLink
          v-if="authStore.isStaff"
          to="/admin"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill border border-brand/30 bg-brand/5 text-brand text-xs font-semibold hover:bg-brand/10 transition"
        >
          <ShieldCheck class="w-3.5 h-3.5" />
          <span>Admin Panel</span>
        </RouterLink>

        <button
          type="button"
          @click="handleLogout"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill border border-line text-xs font-semibold text-ink-muted hover:text-error hover:border-red-200 hover:bg-red-50 transition"
        >
          <LogOut class="w-3.5 h-3.5" />
          <span>Sign Out</span>
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
      <!-- Left Sidebar Navigation (Hidden on Print) -->
      <aside class="lg:col-span-3 print:hidden">
        <nav
          aria-label="Account Navigation"
          class="bg-white rounded-card border border-line p-2 shadow-card flex lg:flex-col gap-1 overflow-x-auto"
        >
          <RouterLink
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors"
            :class="
              isActive(item)
                ? 'bg-brand text-white shadow-sm'
                : 'text-ink-muted hover:text-ink hover:bg-surface'
            "
          >
            <component
              :is="item.icon"
              class="w-4 h-4 shrink-0"
              :class="isActive(item) ? 'text-white' : 'text-ink-muted'"
            />
            <span>{{ item.label }}</span>
          </RouterLink>
        </nav>
      </aside>

      <!-- Main Account Content Area -->
      <div class="lg:col-span-9 print:col-span-12">
        <router-view />
      </div>
    </div>
  </div>
</template>
