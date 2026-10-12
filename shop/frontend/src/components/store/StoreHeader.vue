<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
  Menu as MenuIcon,
  Search,
  Heart,
  ShoppingBag,
  User,
  ChevronDown,
  ArrowRight,
  ShieldCheck,
  LogOut,
} from 'lucide-vue-next'
import { useSettingsStore } from '@/stores/settings'
import { useMenuStore } from '@/stores/menu'
import { useCartStore } from '@/stores/cart'
import { useWishlistStore } from '@/stores/wishlist'
import { useAuthStore } from '@/stores/auth'
import AnnouncementBar from './AnnouncementBar.vue'

const emit = defineEmits(['openDrawer', 'openSearch', 'openCart'])

const router = useRouter()
const settingsStore = useSettingsStore()
const menuStore = useMenuStore()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()
const authStore = useAuthStore()

const isScrolled = ref(false)
const activeMegaItem = ref(null)
const userMenuOpen = ref(false)
const headerRef = ref(null)

let openTimeout = null
let closeTimeout = null

const handleScroll = () => {
  isScrolled.value = window.scrollY > 24
}

const openMegaMenu = (item) => {
  clearTimeout(closeTimeout)
  if (!item.columns?.length && !item.children?.length) {
    activeMegaItem.value = null
    return
  }
  openTimeout = setTimeout(() => {
    activeMegaItem.value = item
  }, 120)
}

const closeMegaMenu = () => {
  clearTimeout(openTimeout)
  closeTimeout = setTimeout(() => {
    activeMegaItem.value = null
  }, 180)
}

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    activeMegaItem.value = null
    userMenuOpen.value = false
  }
}

const handleOutsideClick = (e) => {
  if (headerRef.value && !headerRef.value.contains(e.target)) {
    activeMegaItem.value = null
    userMenuOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  window.addEventListener('keydown', handleKeyDown)
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('keydown', handleKeyDown)
  window.removeEventListener('click', handleOutsideClick)
  clearTimeout(openTimeout)
  clearTimeout(closeTimeout)
})

const mainMenuItems = computed(() => {
  return menuStore.mainMenu?.items || []
})
</script>

<template>
  <header
    ref="headerRef"
    class="sticky top-0 z-40 bg-white/95 backdrop-blur-md transition-all duration-200 border-b border-line/80"
    :class="{ 'shadow-sm': isScrolled }"
  >
    <!-- Announcement Bar -->
    <AnnouncementBar />

    <!-- Main Navigation Bar -->
    <div
      class="max-w-container mx-auto px-4 sm:px-6 lg:px-8 transition-all duration-200 flex items-center justify-between"
      :class="isScrolled ? 'py-2.5 sm:py-3' : 'py-3.5 sm:py-4'"
    >
      <!-- Left side: Mobile Hamburger + Logo -->
      <div class="flex items-center gap-3 sm:gap-6">
        <!-- Mobile hamburger toggle -->
        <button
          type="button"
          @click="emit('openDrawer')"
          class="lg:hidden p-2 -ml-2 rounded-lg text-ink hover:text-brand hover:bg-surface transition"
          aria-label="Open mobile navigation drawer"
        >
          <MenuIcon class="w-5 h-5" />
        </button>

        <!-- Brand Logo / Text Fallback -->
        <RouterLink
          to="/"
          class="flex items-center gap-2.5 group focus:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded-lg"
          aria-label="Home"
        >
          <img
            v-if="settingsStore.logoUrl"
            :src="settingsStore.logoUrl"
            :alt="settingsStore.siteName"
            class="h-7 sm:h-8 max-w-[160px] object-contain transition-transform duration-200 group-hover:scale-[1.02]"
          />
          <span
            v-else
            class="text-lg sm:text-xl font-bold tracking-tight text-ink group-hover:text-brand transition-colors"
          >
            {{ settingsStore.siteName }}
          </span>
        </RouterLink>
      </div>

      <!-- Center: Desktop Mega Menu Navigation -->
      <nav
        class="hidden lg:flex items-center gap-1 xl:gap-2"
        aria-label="Main Navigation"
      >
        <div
          v-for="item in mainMenuItems"
          :key="'top-' + item.id"
          class="relative"
          @mouseenter="openMegaMenu(item)"
          @mouseleave="closeMegaMenu"
        >
          <RouterLink
            :to="item.url || '#'"
            class="inline-flex items-center gap-1 px-3 py-2 rounded-pill text-xs font-semibold text-ink hover:text-brand hover:bg-surface/80 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
            :class="{ 'text-brand bg-surface/80': activeMegaItem?.id === item.id }"
            @focus="openMegaMenu(item)"
          >
            <span>{{ item.label }}</span>
            <span
              v-if="item.badge_text"
              class="text-[10px] font-semibold px-1.5 py-0.2 rounded-pill bg-brand/10 text-brand"
            >
              {{ item.badge_text }}
            </span>
            <ChevronDown
              v-if="(item.columns && item.columns.length > 0) || (item.children && item.children.length > 0)"
              class="w-3.5 h-3.5 text-ink-muted transition-transform duration-200"
              :class="{ 'rotate-180': activeMegaItem?.id === item.id }"
            />
          </RouterLink>
        </div>
      </nav>

      <!-- Right side: Search, Wishlist, Account, Cart -->
      <div class="flex items-center gap-1 sm:gap-2">
        <!-- Search trigger button -->
        <button
          type="button"
          @click="emit('openSearch')"
          class="p-2 sm:p-2.5 rounded-full text-ink hover:text-brand hover:bg-surface transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
          aria-label="Search products"
        >
          <Search class="w-4 h-4 sm:w-5 sm:h-5" />
        </button>

        <!-- Wishlist link with badge -->
        <RouterLink
          to="/wishlist"
          class="relative p-2 sm:p-2.5 rounded-full text-ink hover:text-brand hover:bg-surface transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand hidden sm:inline-flex"
          aria-label="View wishlist"
        >
          <Heart class="w-4 h-4 sm:w-5 sm:h-5" />
          <span
            v-if="wishlistStore.count > 0"
            class="absolute top-1 right-1 w-4 h-4 rounded-full bg-brand text-white text-[10px] font-bold flex items-center justify-center"
          >
            {{ wishlistStore.count }}
          </span>
        </RouterLink>

        <!-- Account button & Dropdown -->
        <div class="relative">
          <button
            type="button"
            @click="userMenuOpen = !userMenuOpen"
            class="p-2 sm:p-2.5 rounded-full text-ink hover:text-brand hover:bg-surface transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand flex items-center gap-1"
            aria-label="User account"
            :aria-expanded="userMenuOpen"
          >
            <User class="w-4 h-4 sm:w-5 sm:h-5" />
          </button>

          <!-- User dropdown menu -->
          <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="userMenuOpen"
              class="absolute right-0 mt-2 w-48 sm:w-56 bg-white rounded-card shadow-lg border border-line py-2 z-50 text-xs"
              @click="userMenuOpen = false"
            >
              <template v-if="authStore.isAuthenticated">
                <div class="px-3.5 py-2 border-b border-line">
                  <p class="font-semibold text-ink truncate">{{ authStore.user?.name }}</p>
                  <p class="text-[11px] text-ink-muted truncate">{{ authStore.user?.email }}</p>
                </div>

                <RouterLink
                  v-if="authStore.isStaff"
                  to="/admin"
                  class="flex items-center gap-2 px-3.5 py-2 text-brand font-semibold hover:bg-surface transition"
                >
                  <ShieldCheck class="w-3.5 h-3.5" />
                  <span>Admin Dashboard</span>
                </RouterLink>

                <RouterLink
                  to="/account"
                  class="flex items-center gap-2 px-3.5 py-2 text-ink hover:bg-surface transition"
                >
                  <User class="w-3.5 h-3.5 text-ink-muted" />
                  <span>Account Overview</span>
                </RouterLink>

                <RouterLink
                  to="/account/orders"
                  class="flex items-center gap-2 px-3.5 py-2 text-ink hover:bg-surface transition"
                >
                  <ShoppingBag class="w-3.5 h-3.5 text-ink-muted" />
                  <span>My Orders</span>
                </RouterLink>

                <div class="border-t border-line mt-1 pt-1">
                  <button
                    type="button"
                    @click="authStore.logout()"
                    class="w-full text-left flex items-center gap-2 px-3.5 py-2 text-error hover:bg-red-50 transition"
                  >
                    <LogOut class="w-3.5 h-3.5" />
                    <span>Sign Out</span>
                  </button>
                </div>
              </template>

              <template v-else>
                <div class="px-3.5 py-2 border-b border-line text-ink-muted">
                  Welcome to {{ settingsStore.siteName }}
                </div>
                <RouterLink
                  to="/login"
                  class="flex items-center justify-between px-3.5 py-2.5 font-semibold text-brand hover:bg-surface transition"
                >
                  <span>Sign In</span>
                  <ArrowRight class="w-3.5 h-3.5" />
                </RouterLink>
                <RouterLink
                  to="/register"
                  class="flex items-center justify-between px-3.5 py-2 text-ink hover:bg-surface transition"
                >
                  <span>Create Account</span>
                </RouterLink>
              </template>
            </div>
          </Transition>
        </div>

        <!-- Cart drawer trigger button with item count badge -->
        <button
          type="button"
          @click="emit('openCart')"
          class="relative p-2 sm:p-2.5 rounded-full text-ink hover:text-brand hover:bg-surface transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand"
          aria-label="Shopping bag"
        >
          <ShoppingBag class="w-4 h-4 sm:w-5 sm:h-5" />
          <span
            v-if="cartStore.itemCount > 0"
            class="absolute top-1 right-1 w-4 h-4 rounded-full bg-brand text-white text-[10px] font-bold flex items-center justify-center animate-in zoom-in-50 duration-150"
          >
            {{ cartStore.itemCount }}
          </span>
        </button>
      </div>
    </div>

    <!-- MEGA MENU DROPDOWN PANEL (Desktop) -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div
        v-if="activeMegaItem && ((activeMegaItem.columns && activeMegaItem.columns.length > 0) || (activeMegaItem.children && activeMegaItem.children.length > 0))"
        class="absolute left-0 right-0 top-full bg-white border-b border-line shadow-2xl rounded-b-2xl overflow-hidden z-30"
        @mouseenter="openMegaMenu(activeMegaItem)"
        @mouseleave="closeMegaMenu"
      >
        <div class="max-w-container mx-auto px-6 lg:px-8 py-8">
          <div class="grid grid-cols-12 gap-8">
            <!-- Left / Center: Columns of Links (3 to 5 columns) -->
            <div
              class="grid gap-6"
              :class="activeMegaItem.featured_card ? 'col-span-8 sm:col-span-9' : 'col-span-12'"
              :style="{
                gridTemplateColumns: `repeat(${Math.min(activeMegaItem.columns?.length || 3, 5)}, minmax(0, 1fr))`
              }"
            >
              <!-- Structured Columns -->
              <template v-if="activeMegaItem.columns && activeMegaItem.columns.length > 0">
                <div
                  v-for="(col, colIdx) in activeMegaItem.columns"
                  :key="'mega-col-' + colIdx"
                  class="space-y-3"
                >
                  <h4 v-if="col.heading" class="text-xs font-bold uppercase tracking-wider text-ink border-b border-line/60 pb-2">
                    {{ col.heading }}
                  </h4>
                  <ul class="space-y-2 text-xs">
                    <li v-for="link in col.links" :key="'link-' + link.id">
                      <RouterLink
                        :to="link.url || '#'"
                        @click="activeMegaItem = null"
                        class="inline-flex items-center gap-1.5 text-ink-muted hover:text-brand hover:translate-x-0.5 transition-all py-0.5"
                      >
                        <span>{{ link.label }}</span>
                        <span
                          v-if="link.badge_text"
                          class="text-[9px] font-bold px-1.5 py-0.2 rounded-pill bg-brand/10 text-brand"
                        >
                          {{ link.badge_text }}
                        </span>
                      </RouterLink>
                    </li>
                  </ul>
                </div>
              </template>

              <!-- Fallback Children List -->
              <template v-else-if="activeMegaItem.children">
                <div
                  v-for="sub in activeMegaItem.children"
                  :key="'simple-sub-' + sub.id"
                  class="space-y-2"
                >
                  <RouterLink
                    :to="sub.url || '#'"
                    @click="activeMegaItem = null"
                    class="block text-xs font-medium text-ink hover:text-brand transition"
                  >
                    {{ sub.label }}
                  </RouterLink>
                </div>
              </template>
            </div>

            <!-- Right: Featured Image Card -->
            <div
              v-if="activeMegaItem.featured_card"
              class="col-span-4 sm:col-span-3 flex flex-col"
            >
              <div class="h-full rounded-card border border-line overflow-hidden bg-surface flex flex-col justify-between group hover:shadow-md transition">
                <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                  <img
                    v-if="activeMegaItem.featured_card.image"
                    :src="activeMegaItem.featured_card.image"
                    :alt="activeMegaItem.featured_card.title || 'Featured item'"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                  />
                  <div
                    v-if="activeMegaItem.featured_card.badge"
                    class="absolute top-3 left-3 px-2 py-0.5 rounded-pill bg-white/90 backdrop-blur-sm text-[10px] font-bold text-ink"
                  >
                    {{ activeMegaItem.featured_card.badge }}
                  </div>
                </div>

                <div class="p-4 flex flex-col justify-between flex-1">
                  <div>
                    <h5 class="text-sm font-semibold text-ink">
                      {{ activeMegaItem.featured_card.title }}
                    </h5>
                    <p
                      v-if="activeMegaItem.featured_card.subtitle"
                      class="text-xs text-ink-muted mt-1 line-clamp-2"
                    >
                      {{ activeMegaItem.featured_card.subtitle }}
                    </p>
                  </div>

                  <RouterLink
                    v-if="activeMegaItem.featured_card.button_url"
                    :to="activeMegaItem.featured_card.button_url"
                    @click="activeMegaItem = null"
                    class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-brand hover:opacity-85 transition"
                  >
                    <span>{{ activeMegaItem.featured_card.button_text || 'Discover Now' }}</span>
                    <ArrowRight class="w-3.5 h-3.5" />
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </header>
</template>
