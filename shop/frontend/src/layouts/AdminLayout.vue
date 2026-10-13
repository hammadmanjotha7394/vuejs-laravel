<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter, RouterLink, useRoute } from 'vue-router'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import ToastContainer from '@/components/ui/ToastContainer.vue'
import {
  LayoutDashboard,
  ShoppingBag,
  Package,
  FolderTree,
  Award,
  Users,
  Star,
  TicketPercent,
  MessageSquare,
  Megaphone,
  Compass,
  FileText,
  CircleDollarSign,
  Shield,
  Settings as SettingsIcon,
  Mail,
  Activity,
  LogOut,
  Store,
  Menu,
  X,
  Bell,
  Search,
  PanelLeftClose,
  PanelLeftOpen,
  ChevronDown,
  User,
  AlertTriangle,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const sidebarCollapsed = ref(false)
const mobileSidebarOpen = ref(false)
const searchQuery = ref('')
const searchDropdownOpen = ref(false)
const notifOpen = ref(false)
const profileOpen = ref(false)

const notifications = ref({
  unread_count: 0,
  new_orders_count: 0,
  open_chats_count: 0,
  low_stock_count: 0,
  pending_reviews_count: 0,
  items: [],
})

const can = (perm) => authStore.hasPermission(perm)

// Full permission-gated sidebar structure matching Step 14 specification
const sidebarSections = computed(() => {
  const sections = [
    {
      heading: 'Overview & Store',
      items: [
        {
          label: 'Dashboard',
          to: '/admin',
          exact: true,
          icon: LayoutDashboard,
          visible: true,
          keywords: 'home metrics overview charts',
        },
        {
          label: 'Orders',
          to: '/admin/orders',
          icon: ShoppingBag,
          visible: can('orders.view'),
          badge: notifications.value.new_orders_count || null,
          keywords: 'sales shipments fulfillment refunds',
        },
        {
          label: 'Products',
          to: '/admin/products',
          icon: Package,
          visible: can('products.view'),
          keywords: 'catalog inventory variants stock',
        },
        {
          label: 'Categories',
          to: '/admin/categories',
          icon: FolderTree,
          visible: can('categories.manage'),
          keywords: 'collections taxonomy tree',
        },
        {
          label: 'Brands',
          to: '/admin/brands',
          icon: Award,
          visible: can('brands.manage'),
          keywords: 'makers labels vendors',
        },
        {
          label: 'Customers',
          to: '/admin/customers',
          icon: Users,
          visible: can('users.view'),
          keywords: 'buyers shoppers accounts',
        },
        {
          label: 'Reviews',
          to: '/admin/reviews',
          icon: Star,
          visible: can('reviews.manage'),
          badge: notifications.value.pending_reviews_count || null,
          keywords: 'ratings testimonials moderation',
        },
        {
          label: 'Coupons',
          to: '/admin/coupons',
          icon: TicketPercent,
          visible: can('coupons.manage'),
          keywords: 'discounts promo codes',
        },
        {
          label: 'Live Chat',
          to: '/admin/chat',
          icon: MessageSquare,
          visible: can('chat.manage'),
          badge: notifications.value.open_chats_count || null,
          keywords: 'support messages inbox',
        },
      ],
    },
    {
      heading: 'Marketing & Content',
      items: [
        {
          label: 'Marketing (Hero & Popups)',
          to: '/admin/marketing',
          icon: Megaphone,
          visible: can('hero.manage') || can('popups.manage'),
          keywords: 'hero slides popups newsletter banners',
        },
        {
          label: 'Menus & Mega Menu',
          to: '/admin/menus',
          icon: Compass,
          visible: can('menus.manage'),
          keywords: 'navigation header footer links',
        },
        {
          label: 'CMS Pages',
          to: '/admin/pages',
          icon: FileText,
          visible: can('pages.manage'),
          keywords: 'about faq terms privacy content',
        },
      ],
    },
    {
      heading: 'Finance & Administration',
      items: [
        {
          label: 'Finance (Revenue & Payouts)',
          to: '/admin/finance',
          icon: CircleDollarSign,
          visible: can('finance.view'),
          keywords: 'revenue payouts refunds tax reports',
        },
        {
          label: 'Staff & Users',
          to: '/admin/users',
          icon: Users,
          visible: can('users.view'),
          keywords: 'team admins employees',
        },
        {
          label: 'Roles & Permissions',
          to: '/admin/roles',
          icon: Shield,
          visible: can('roles.manage'),
          keywords: 'security access permissions',
        },
        {
          label: 'Site Settings',
          to: '/admin/settings',
          icon: SettingsIcon,
          visible: can('settings.view'),
          keywords: 'general stripe email smtp shipping tax seo',
        },
        {
          label: 'Email Templates',
          to: '/admin/email-templates',
          icon: Mail,
          visible: can('mail.manage'),
          keywords: 'notifications transactional emails',
        },
        {
          label: 'Activity Log',
          to: '/admin/activity-log',
          icon: Activity,
          visible: can('activity.view'),
          keywords: 'audit trail history logs',
        },
      ],
    },
  ]

  return sections
    .map((sec) => ({
      ...sec,
      items: sec.items.filter((item) => item.visible),
    }))
    .filter((sec) => sec.items.length > 0)
})

const flatVisibleItems = computed(() =>
  sidebarSections.value.flatMap((s) => s.items)
)

const filteredSearchItems = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return flatVisibleItems.value.slice(0, 6)
  return flatVisibleItems.value.filter(
    (item) =>
      item.label.toLowerCase().includes(q) ||
      (item.keywords && item.keywords.toLowerCase().includes(q))
  )
})

const activeRoleName = computed(() => authStore.roles[0] || 'staff')

const userInitials = computed(() => {
  const name = authStore.user?.name || 'A'
  return name
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
})

const isActiveRoute = (item) => {
  if (item.exact) {
    return route.path === item.to
  }
  return route.path === item.to || route.path.startsWith(item.to + '/')
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const navigateFromSearch = (to) => {
  searchDropdownOpen.value = false
  searchQuery.value = ''
  router.push(to)
}

const fetchNotifications = async () => {
  try {
    const res = await apiClient.get('/admin/dashboard')
    if (res.data?.data?.notifications) {
      notifications.value = res.data.data.notifications
    }
  } catch (e) {
    // Ignore background notification errors
  }
}

const handleKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    searchDropdownOpen.value = true
  } else if (e.key === 'Escape') {
    searchDropdownOpen.value = false
    notifOpen.value = false
    profileOpen.value = false
    mobileSidebarOpen.value = false
  }
}

watch(
  () => route.fullPath,
  () => {
    mobileSidebarOpen.value = false
    notifOpen.value = false
    profileOpen.value = false
    searchDropdownOpen.value = false
  }
)

onMounted(() => {
  settingsStore.fetchPublicSettings()
  fetchNotifications()
  window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <div class="min-h-screen bg-surface flex text-ink">
    <!-- Desktop Collapsible Left Sidebar -->
    <aside
      class="hidden lg:flex flex-col bg-white border-r border-line shrink-0 transition-all duration-200 sticky top-0 h-screen z-30"
      :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    >
      <!-- Brand Header -->
      <div class="h-16 px-4 border-b border-line flex items-center justify-between gap-2 shrink-0">
        <RouterLink
          to="/admin"
          class="flex items-center gap-2.5 font-bold tracking-tight text-ink min-w-0"
        >
          <span class="w-8 h-8 rounded-xl bg-brand text-white flex items-center justify-center text-xs font-bold shrink-0">
            {{ (settingsStore.siteName || 'B')[0] }}
          </span>
          <div v-if="!sidebarCollapsed" class="truncate">
            <span class="block text-sm font-bold leading-tight truncate">
              {{ settingsStore.siteName || 'Boutique' }}
            </span>
            <span class="block text-[10px] font-semibold uppercase tracking-wider text-brand">
              Admin Console
            </span>
          </div>
        </RouterLink>

        <button
          type="button"
          @click="sidebarCollapsed = !sidebarCollapsed"
          class="p-1.5 rounded-lg text-ink-muted hover:text-ink hover:bg-surface transition"
          :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
          <PanelLeftOpen v-if="sidebarCollapsed" class="w-4 h-4" />
          <PanelLeftClose v-else class="w-4 h-4" />
        </button>
      </div>

      <!-- Navigation Sections -->
      <div class="flex-1 overflow-y-auto py-4 px-3 space-y-5">
        <div v-for="section in sidebarSections" :key="section.heading">
          <p
            v-if="!sidebarCollapsed"
            class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-ink-muted"
          >
            {{ section.heading }}
          </p>
          <nav class="space-y-1">
            <RouterLink
              v-for="item in section.items"
              :key="item.to"
              :to="item.to"
              :title="sidebarCollapsed ? item.label : ''"
              class="flex items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition-colors"
              :class="[
                isActiveRoute(item)
                  ? 'bg-brand text-white shadow-2xs'
                  : 'text-ink-muted hover:text-ink hover:bg-surface',
                sidebarCollapsed ? 'justify-center' : '',
              ]"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <component
                  :is="item.icon"
                  class="w-4 h-4 shrink-0"
                  :class="isActiveRoute(item) ? 'text-white' : 'text-ink-muted'"
                />
                <span v-if="!sidebarCollapsed" class="truncate">{{ item.label }}</span>
              </div>

              <span
                v-if="!sidebarCollapsed && item.badge"
                class="px-1.5 py-0.2 rounded-pill text-[10px] font-bold"
                :class="
                  isActiveRoute(item)
                    ? 'bg-white/20 text-white'
                    : 'bg-brand/10 text-brand'
                "
              >
                {{ item.badge }}
              </span>
            </RouterLink>
          </nav>
        </div>
      </div>

      <!-- Sidebar Footer: Role Pill -->
      <div class="p-3 border-t border-line shrink-0">
        <div
          v-if="!sidebarCollapsed"
          class="px-3 py-2 rounded-xl bg-surface border border-line flex items-center justify-between text-xs"
        >
          <span class="text-ink-muted font-medium">Active Role</span>
          <span class="px-2 py-0.5 rounded-pill bg-brand/10 text-brand font-bold text-[10px] uppercase">
            {{ activeRoleName.replace('_', ' ') }}
          </span>
        </div>
        <div v-else class="flex justify-center">
          <span
            class="w-8 h-8 rounded-xl bg-brand/10 text-brand font-bold text-[10px] uppercase flex items-center justify-center"
            :title="activeRoleName"
          >
            {{ activeRoleName.slice(0, 2) }}
          </span>
        </div>
      </div>
    </aside>

    <!-- Mobile Navigation Slide-Over Drawer -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="mobileSidebarOpen"
          class="fixed inset-0 z-50 lg:hidden flex bg-black/40 backdrop-blur-xs"
          @click.self="mobileSidebarOpen = false"
        >
          <aside class="w-72 max-w-[85vw] bg-white h-full flex flex-col shadow-2xl border-r border-line">
            <div class="h-16 px-4 border-b border-line flex items-center justify-between">
              <RouterLink to="/admin" class="flex items-center gap-2.5 font-bold text-ink">
                <span class="w-8 h-8 rounded-xl bg-brand text-white flex items-center justify-center text-xs font-bold">
                  {{ (settingsStore.siteName || 'B')[0] }}
                </span>
                <span class="text-sm font-bold">{{ settingsStore.siteName || 'Boutique' }} Admin</span>
              </RouterLink>
              <button
                type="button"
                @click="mobileSidebarOpen = false"
                class="p-2 rounded-full text-ink-muted hover:text-ink hover:bg-surface"
              >
                <X class="w-4 h-4" />
              </button>
            </div>

            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-5">
              <div v-for="section in sidebarSections" :key="'m-' + section.heading">
                <p class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-ink-muted">
                  {{ section.heading }}
                </p>
                <nav class="space-y-1">
                  <RouterLink
                    v-for="item in section.items"
                    :key="'m-' + item.to"
                    :to="item.to"
                    class="flex items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl text-xs font-semibold transition"
                    :class="
                      isActiveRoute(item)
                        ? 'bg-brand text-white'
                        : 'text-ink-muted hover:text-ink hover:bg-surface'
                    "
                  >
                    <div class="flex items-center gap-2.5">
                      <component :is="item.icon" class="w-4 h-4 shrink-0" />
                      <span>{{ item.label }}</span>
                    </div>
                    <span
                      v-if="item.badge"
                      class="px-1.5 py-0.2 rounded-pill text-[10px] font-bold"
                      :class="isActiveRoute(item) ? 'bg-white/20 text-white' : 'bg-brand/10 text-brand'"
                    >
                      {{ item.badge }}
                    </span>
                  </RouterLink>
                </nav>
              </div>
            </div>
          </aside>
        </div>
      </Transition>
    </Teleport>

    <!-- Main Right-Side Column: Sticky Topbar + Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Bar -->
      <header class="h-16 bg-white border-b border-line sticky top-0 z-20 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-3">
        <!-- Left: Mobile Hamburger & Quick Search Bar -->
        <div class="flex items-center gap-3 flex-1 max-w-md">
          <button
            type="button"
            @click="mobileSidebarOpen = true"
            class="lg:hidden p-2 rounded-xl border border-line text-ink-muted hover:text-ink hover:bg-surface transition"
            aria-label="Open navigation drawer"
          >
            <Menu class="w-4 h-4" />
          </button>

          <!-- Admin Quick Search / Command Jump -->
          <div class="relative flex-1">
            <Search class="w-3.5 h-3.5 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Jump to admin page or module (Ctrl+K)..."
              class="w-full pl-9 pr-14 py-2 rounded-pill border border-line bg-surface/60 text-xs text-ink placeholder:text-ink-muted focus:bg-white focus:outline-none focus:border-brand transition"
              @focus="searchDropdownOpen = true"
            />
            <span class="hidden sm:inline-block absolute right-3 top-1/2 -translate-y-1/2 px-1.5 py-0.5 rounded bg-white border border-line text-[10px] font-mono text-ink-muted pointer-events-none">
              Ctrl+K
            </span>

            <!-- Quick Jump Dropdown -->
            <div
              v-if="searchDropdownOpen"
              class="absolute left-0 right-0 mt-2 bg-white rounded-card border border-line shadow-xl py-2 z-50 max-h-72 overflow-y-auto"
            >
              <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-ink-muted flex items-center justify-between">
                <span>Quick Navigation</span>
                <button
                  type="button"
                  @click="searchDropdownOpen = false"
                  class="text-ink-muted hover:text-ink"
                >
                  Esc
                </button>
              </div>
              <div v-if="filteredSearchItems.length === 0" class="px-3 py-4 text-xs text-ink-muted text-center">
                No matching admin sections found.
              </div>
              <button
                v-for="item in filteredSearchItems"
                :key="'q-' + item.to"
                type="button"
                @click="navigateFromSearch(item.to)"
                class="w-full px-3.5 py-2 text-left text-xs flex items-center gap-2.5 text-ink hover:bg-surface transition"
              >
                <component :is="item.icon" class="w-3.5 h-3.5 text-brand shrink-0" />
                <span class="font-semibold">{{ item.label }}</span>
                <span class="ml-auto text-[10px] font-mono text-ink-muted">{{ item.to }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Right: Storefront Link, Notifications Bell & Profile Menu -->
        <div class="flex items-center gap-2 sm:gap-3">
          <RouterLink
            to="/"
            target="_blank"
            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-pill border border-line text-xs font-semibold text-ink-muted hover:text-brand hover:border-brand/40 transition"
          >
            <Store class="w-3.5 h-3.5" />
            <span>Storefront</span>
          </RouterLink>

          <!-- Notifications Bell Dropdown -->
          <div class="relative">
            <button
              type="button"
              @click="notifOpen = !notifOpen; profileOpen = false"
              class="relative p-2 rounded-full border border-line text-ink-muted hover:text-ink hover:bg-surface transition"
              aria-label="Notifications"
            >
              <Bell class="w-4 h-4" />
              <span
                v-if="notifications.unread_count > 0"
                class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-brand text-white text-[10px] font-bold flex items-center justify-center"
              >
                {{ notifications.unread_count }}
              </span>
            </button>

            <div
              v-if="notifOpen"
              class="absolute right-0 mt-2 w-80 bg-white rounded-card border border-line shadow-xl py-2 z-50 text-xs"
            >
              <div class="px-4 py-2 border-b border-line flex items-center justify-between">
                <span class="font-bold text-ink">Notifications</span>
                <span class="px-2 py-0.5 rounded-pill bg-surface border border-line text-[10px] font-semibold text-ink-muted">
                  {{ notifications.unread_count }} active
                </span>
              </div>

              <div v-if="!notifications.items?.length" class="px-4 py-6 text-center text-ink-muted">
                All caught up! No urgent store alerts right now.
              </div>

              <div v-else class="divide-y divide-line max-h-72 overflow-y-auto">
                <RouterLink
                  v-for="n in notifications.items"
                  :key="n.id"
                  :to="n.to"
                  class="px-4 py-3 flex items-start gap-3 hover:bg-surface transition block"
                  @click="notifOpen = false"
                >
                  <div
                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5"
                    :class="
                      n.type === 'stock'
                        ? 'bg-amber-50 text-amber-700'
                        : 'bg-brand/10 text-brand'
                    "
                  >
                    <AlertTriangle v-if="n.type === 'stock'" class="w-3.5 h-3.5" />
                    <Bell v-else class="w-3.5 h-3.5" />
                  </div>
                  <div class="min-w-0">
                    <p class="font-bold text-ink truncate">{{ n.title }}</p>
                    <p class="text-[11px] text-ink-muted mt-0.5">{{ n.subtitle }}</p>
                  </div>
                </RouterLink>
              </div>
            </div>
          </div>

          <!-- Profile Menu Dropdown -->
          <div class="relative">
            <button
              type="button"
              @click="profileOpen = !profileOpen; notifOpen = false"
              class="flex items-center gap-2 pl-1.5 pr-2.5 py-1 rounded-pill border border-line hover:bg-surface transition"
            >
              <span class="w-7 h-7 rounded-full bg-brand/10 text-brand font-bold text-xs flex items-center justify-center">
                {{ userInitials }}
              </span>
              <span class="hidden sm:block text-xs font-semibold text-ink max-w-[120px] truncate">
                {{ authStore.user?.name }}
              </span>
              <ChevronDown class="w-3.5 h-3.5 text-ink-muted" />
            </button>

            <div
              v-if="profileOpen"
              class="absolute right-0 mt-2 w-56 bg-white rounded-card border border-line shadow-xl py-2 z-50 text-xs"
            >
              <div class="px-4 py-2.5 border-b border-line">
                <p class="font-bold text-ink truncate">{{ authStore.user?.name }}</p>
                <p class="text-[11px] text-ink-muted truncate">{{ authStore.user?.email }}</p>
                <span class="inline-block mt-1.5 px-2 py-0.5 rounded-pill bg-brand/10 text-brand text-[10px] font-bold uppercase">
                  {{ activeRoleName.replace('_', ' ') }}
                </span>
              </div>

              <RouterLink
                to="/account"
                class="px-4 py-2 flex items-center gap-2 text-ink hover:bg-surface transition"
                @click="profileOpen = false"
              >
                <User class="w-3.5 h-3.5 text-ink-muted" />
                <span>My Account Profile</span>
              </RouterLink>

              <RouterLink
                to="/"
                class="px-4 py-2 flex items-center gap-2 text-ink hover:bg-surface transition"
                @click="profileOpen = false"
              >
                <Store class="w-3.5 h-3.5 text-ink-muted" />
                <span>View Live Storefront</span>
              </RouterLink>

              <div class="border-t border-line mt-1 pt-1">
                <button
                  type="button"
                  @click="handleLogout"
                  class="w-full text-left px-4 py-2 flex items-center gap-2 text-error hover:bg-red-50 transition font-semibold"
                >
                  <LogOut class="w-3.5 h-3.5" />
                  <span>Sign Out</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Main Admin Content Area on Soft Grey Background -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
        <RouterView />
      </main>
    </div>

    <!-- Global Toast Notification Stack -->
    <ToastContainer />
  </div>
</template>
