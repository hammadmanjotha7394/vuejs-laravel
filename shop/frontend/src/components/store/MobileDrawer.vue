<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import {
  X,
  Search,
  ChevronDown,
  ShoppingBag,
  Heart,
  User,
  ShieldCheck,
  LogOut,
} from 'lucide-vue-next'
import { useMenuStore } from '@/stores/menu'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useWishlistStore } from '@/stores/wishlist'
import { useSettingsStore } from '@/stores/settings'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'openSearch'])

const router = useRouter()
const menuStore = useMenuStore()
const authStore = useAuthStore()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()
const settingsStore = useSettingsStore()

const drawerSearch = ref('')
const expandedMenus = ref(new Set())

const toggleAccordion = (id) => {
  if (expandedMenus.value.has(id)) {
    expandedMenus.value.delete(id)
  } else {
    expandedMenus.value.add(id)
  }
}

const isExpanded = (id) => expandedMenus.value.has(id)

const submitDrawerSearch = () => {
  if (!drawerSearch.value.trim()) return
  const term = drawerSearch.value.trim()
  drawerSearch.value = ''
  emit('close')
  router.push(`/shop?q=${encodeURIComponent(term)}`)
}

const handleLinkClick = () => {
  emit('close')
}

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && props.isOpen) {
    emit('close')
  }
}

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      document.body.style.overflow = 'hidden'
      window.addEventListener('keydown', handleKeyDown)
    } else {
      document.body.style.overflow = ''
      window.removeEventListener('keydown', handleKeyDown)
    }
  }
)

onUnmounted(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
  <div>
    <!-- Backdrop -->
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm lg:hidden"
        @click="emit('close')"
        aria-hidden="true"
      ></div>
    </Transition>

    <!-- Slide-in Drawer -->
    <Transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="-translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="-translate-x-full"
    >
      <div
        v-if="isOpen"
        class="fixed inset-y-0 left-0 z-50 w-full max-w-xs sm:max-w-sm bg-white shadow-2xl flex flex-col lg:hidden border-r border-line"
        role="dialog"
        aria-modal="true"
        aria-label="Mobile Navigation"
      >
        <!-- Top bar: Brand & Close -->
        <div class="p-4 flex items-center justify-between border-b border-line bg-surface/50">
          <RouterLink to="/" @click="handleLinkClick" class="flex items-center gap-2">
            <img
              v-if="settingsStore.logoUrl"
              :src="settingsStore.logoUrl"
              :alt="settingsStore.siteName"
              class="h-7 max-w-[140px] object-contain"
            />
            <span v-else class="font-semibold text-ink text-base tracking-tight">
              {{ settingsStore.siteName }}
            </span>
          </RouterLink>

          <button
            type="button"
            @click="emit('close')"
            class="p-1.5 rounded-full text-ink-muted hover:text-ink hover:bg-surface transition"
            aria-label="Close menu"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Search input in top of drawer -->
        <div class="p-4 border-b border-line bg-white">
          <form @submit.prevent="submitDrawerSearch" class="relative">
            <input
              v-model="drawerSearch"
              type="search"
              placeholder="Search products..."
              class="w-full pl-9 pr-4 py-2 bg-surface border border-line rounded-input text-xs sm:text-sm text-ink placeholder:text-ink-muted focus:outline-none focus:border-brand transition"
            />
            <Search class="w-4 h-4 text-ink-muted absolute left-3 top-1/2 -translate-y-1/2" />
          </form>
        </div>

        <!-- Navigation items list with accordion -->
        <div class="flex-1 overflow-y-auto px-4 py-3 divide-y divide-line/40">
          <!-- Main Menu Items -->
          <div v-if="menuStore.mainMenu?.items?.length" class="py-2 space-y-1">
            <div
              v-for="item in menuStore.mainMenu.items"
              :key="'m-item-' + item.id"
              class="rounded-lg overflow-hidden"
            >
              <!-- Item with sub-items (accordion) -->
              <div v-if="(item.columns && item.columns.length > 0) || (item.children && item.children.length > 0)">
                <button
                  type="button"
                  @click="toggleAccordion(item.id)"
                  class="w-full flex items-center justify-between py-2.5 px-3 rounded-lg text-sm font-medium text-ink hover:bg-surface transition"
                >
                  <span class="flex items-center gap-2">
                    {{ item.label }}
                    <span
                      v-if="item.badge_text"
                      class="text-[10px] font-semibold px-1.5 py-0.5 rounded-pill bg-brand/10 text-brand"
                    >
                      {{ item.badge_text }}
                    </span>
                  </span>
                  <ChevronDown
                    class="w-4 h-4 text-ink-muted transition-transform duration-200"
                    :class="{ 'rotate-180': isExpanded(item.id) }"
                  />
                </button>

                <!-- Expanded accordion panel -->
                <div
                  v-show="isExpanded(item.id)"
                  class="pl-4 pr-2 py-1 space-y-1 bg-surface/30 rounded-lg mb-1 border-l-2 border-brand/20 ml-3"
                >
                  <!-- If item has mega menu columns -->
                  <template v-if="item.columns && item.columns.length > 0">
                    <div
                      v-for="(col, colIdx) in item.columns"
                      :key="'col-' + colIdx"
                      class="py-1"
                    >
                      <p v-if="col.heading" class="text-[11px] font-bold text-ink uppercase tracking-wider py-1">
                        {{ col.heading }}
                      </p>
                      <RouterLink
                        v-for="sub in col.links"
                        :key="'sub-' + sub.id"
                        :to="sub.url || '#'"
                        @click="handleLinkClick"
                        class="block py-1.5 px-2 text-xs text-ink-muted hover:text-ink hover:bg-surface rounded transition"
                      >
                        {{ sub.label }}
                      </RouterLink>
                    </div>
                  </template>

                  <!-- Direct children fallback -->
                  <template v-else-if="item.children">
                    <RouterLink
                      v-for="sub in item.children"
                      :key="'child-' + sub.id"
                      :to="sub.url || '#'"
                      @click="handleLinkClick"
                      class="block py-1.5 px-2 text-xs text-ink-muted hover:text-ink hover:bg-surface rounded transition"
                    >
                      {{ sub.label }}
                    </RouterLink>
                  </template>
                </div>
              </div>

              <!-- Single link item -->
              <RouterLink
                v-else
                :to="item.url || '#'"
                @click="handleLinkClick"
                class="flex items-center justify-between py-2.5 px-3 rounded-lg text-sm font-medium text-ink hover:bg-surface transition"
              >
                <span>{{ item.label }}</span>
                <span
                  v-if="item.badge_text"
                  class="text-[10px] font-semibold px-1.5 py-0.5 rounded-pill bg-brand/10 text-brand"
                >
                  {{ item.badge_text }}
                </span>
              </RouterLink>
            </div>
          </div>

          <!-- Fallback direct categories if main menu empty -->
          <div v-else class="py-2 space-y-1">
            <RouterLink
              v-for="cat in menuStore.categories"
              :key="'cat-' + cat.id"
              :to="`/category/${cat.slug}`"
              @click="handleLinkClick"
              class="block py-2 px-3 text-sm font-medium text-ink hover:bg-surface rounded-lg transition"
            >
              {{ cat.name }}
            </RouterLink>
          </div>
        </div>

        <!-- Bottom User Actions & Wishlist/Cart Links -->
        <div class="p-4 border-t border-line bg-surface/50 space-y-2">
          <!-- Wishlist link -->
          <RouterLink
            to="/wishlist"
            @click="handleLinkClick"
            class="flex items-center justify-between py-2 px-3 rounded-lg text-xs font-medium text-ink hover:bg-white transition"
          >
            <div class="flex items-center gap-2.5">
              <Heart class="w-4 h-4 text-ink-muted" />
              <span>Wishlist</span>
            </div>
            <span
              v-if="wishlistStore.count > 0"
              class="px-2 py-0.5 rounded-pill bg-brand text-white text-[10px] font-semibold"
            >
              {{ wishlistStore.count }}
            </span>
          </RouterLink>

          <!-- Account or Login / Register -->
          <template v-if="authStore.isAuthenticated">
            <RouterLink
              v-if="authStore.isStaff"
              to="/admin"
              @click="handleLinkClick"
              class="flex items-center gap-2.5 py-2 px-3 rounded-lg text-xs font-semibold text-brand hover:bg-white transition"
            >
              <ShieldCheck class="w-4 h-4" />
              <span>Admin Dashboard</span>
            </RouterLink>
            <RouterLink
              to="/account"
              @click="handleLinkClick"
              class="flex items-center gap-2.5 py-2 px-3 rounded-lg text-xs font-medium text-ink hover:bg-white transition"
            >
              <User class="w-4 h-4 text-ink-muted" />
              <span>My Account ({{ authStore.user?.name }})</span>
            </RouterLink>
            <button
              type="button"
              @click="authStore.logout(); handleLinkClick()"
              class="w-full flex items-center gap-2.5 py-2 px-3 rounded-lg text-xs font-medium text-error hover:bg-red-50 transition"
            >
              <LogOut class="w-4 h-4" />
              <span>Sign Out</span>
            </button>
          </template>
          <template v-else>
            <div class="grid grid-cols-2 gap-2 pt-1">
              <RouterLink
                to="/login"
                @click="handleLinkClick"
                class="py-2 text-center text-xs font-semibold rounded-pill border border-line bg-white hover:bg-surface text-ink transition"
              >
                Sign In
              </RouterLink>
              <RouterLink
                to="/register"
                @click="handleLinkClick"
                class="py-2 text-center text-xs font-semibold rounded-pill bg-brand text-white hover:opacity-90 transition"
              >
                Register
              </RouterLink>
            </div>
          </template>
        </div>
      </div>
    </Transition>
  </div>
</template>
