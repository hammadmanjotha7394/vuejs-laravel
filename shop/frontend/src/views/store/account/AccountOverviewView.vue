<script setup>
import { ref, onMounted } from 'vue'
import { useHead } from '@unhead/vue'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import {
  ShoppingBag,
  Truck,
  Heart,
  Star,
  MapPin,
  User,
  ArrowRight,
  Package,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const settingsStore = useSettingsStore()

useHead({
  title: () => `Account Overview | ${settingsStore.siteName}`,
})

const loading = ref(true)
const stats = ref({
  total_orders: 0,
  active_orders: 0,
  wishlist_count: 0,
  reviews_count: 0,
})
const defaultAddress = ref(null)
const recentOrders = ref([])

const statusBadgeClass = (status) => {
  const map = {
    pending: 'bg-amber-50 text-amber-800 border-amber-200',
    paid: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    processing: 'bg-blue-50 text-blue-800 border-blue-200',
    shipped: 'bg-indigo-50 text-indigo-800 border-indigo-200',
    delivered: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
    refunded: 'bg-gray-100 text-gray-700 border-gray-300',
  }
  return map[status] || 'bg-surface text-ink border-line'
}

const fetchOverview = async () => {
  loading.value = true
  try {
    const res = await apiClient.get('/account/overview')
    const data = res.data?.data || {}
    stats.value = data.stats || stats.value
    defaultAddress.value = data.default_address || null
    recentOrders.value = data.recent_orders || []
  } catch (e) {
    // Handled gracefully
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchOverview()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top KPI Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <RouterLink
        to="/account/orders"
        class="bg-white rounded-card border border-line p-4 sm:p-5 shadow-card hover:border-brand/40 transition group"
      >
        <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand flex items-center justify-center mb-3">
          <ShoppingBag class="w-4 h-4" />
        </div>
        <p class="text-xl sm:text-2xl font-bold text-ink">
          {{ loading ? '—' : stats.total_orders }}
        </p>
        <p class="text-xs text-ink-muted font-medium mt-0.5 group-hover:text-brand transition">
          Total Orders
        </p>
      </RouterLink>

      <RouterLink
        to="/account/orders"
        class="bg-white rounded-card border border-line p-4 sm:p-5 shadow-card hover:border-brand/40 transition group"
      >
        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center mb-3">
          <Truck class="w-4 h-4" />
        </div>
        <p class="text-xl sm:text-2xl font-bold text-ink">
          {{ loading ? '—' : stats.active_orders }}
        </p>
        <p class="text-xs text-ink-muted font-medium mt-0.5 group-hover:text-brand transition">
          Active Orders
        </p>
      </RouterLink>

      <RouterLink
        to="/account/wishlist"
        class="bg-white rounded-card border border-line p-4 sm:p-5 shadow-card hover:border-brand/40 transition group"
      >
        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mb-3">
          <Heart class="w-4 h-4" />
        </div>
        <p class="text-xl sm:text-2xl font-bold text-ink">
          {{ loading ? '—' : stats.wishlist_count }}
        </p>
        <p class="text-xs text-ink-muted font-medium mt-0.5 group-hover:text-brand transition">
          Wishlist Items
        </p>
      </RouterLink>

      <RouterLink
        to="/account/reviews"
        class="bg-white rounded-card border border-line p-4 sm:p-5 shadow-card hover:border-brand/40 transition group"
      >
        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3">
          <Star class="w-4 h-4" />
        </div>
        <p class="text-xl sm:text-2xl font-bold text-ink">
          {{ loading ? '—' : stats.reviews_count }}
        </p>
        <p class="text-xs text-ink-muted font-medium mt-0.5 group-hover:text-brand transition">
          My Reviews
        </p>
      </RouterLink>
    </div>

    <!-- Profile & Default Address Quick Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Profile Summary -->
      <div class="bg-white rounded-card border border-line p-5 sm:p-6 shadow-card flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-ink flex items-center gap-2">
              <User class="w-4 h-4 text-brand" />
              <span>Personal Profile</span>
            </h2>
            <RouterLink
              to="/account/profile"
              class="text-xs font-semibold text-brand hover:underline"
            >
              Edit Profile
            </RouterLink>
          </div>
          <div class="space-y-1.5 text-xs">
            <p class="font-semibold text-ink text-sm">{{ authStore.user?.name }}</p>
            <p class="text-ink-muted">{{ authStore.user?.email }}</p>
            <p class="text-ink-muted">
              Phone: <span class="text-ink font-medium">{{ authStore.user?.phone || 'Not provided' }}</span>
            </p>
          </div>
        </div>
        <div class="mt-5 pt-4 border-t border-line flex items-center justify-between text-xs">
          <span class="text-ink-muted">Password & account security</span>
          <RouterLink
            to="/account/profile"
            class="inline-flex items-center gap-1 font-semibold text-ink hover:text-brand transition"
          >
            <span>Manage</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </RouterLink>
        </div>
      </div>

      <!-- Default Address Summary -->
      <div class="bg-white rounded-card border border-line p-5 sm:p-6 shadow-card flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-ink flex items-center gap-2">
              <MapPin class="w-4 h-4 text-brand" />
              <span>Default Shipping Address</span>
            </h2>
            <RouterLink
              to="/account/addresses"
              class="text-xs font-semibold text-brand hover:underline"
            >
              Manage Addresses
            </RouterLink>
          </div>

          <div v-if="defaultAddress" class="space-y-1 text-xs text-ink-muted">
            <p class="font-semibold text-ink text-sm flex items-center gap-2">
              <span>{{ defaultAddress.name }}</span>
              <span class="px-2 py-0.5 rounded-pill bg-surface border border-line text-[10px] font-semibold text-ink">
                {{ defaultAddress.label || 'Default' }}
              </span>
            </p>
            <p>{{ defaultAddress.line1 }}<span v-if="defaultAddress.line2">, {{ defaultAddress.line2 }}</span></p>
            <p>{{ defaultAddress.city }}, {{ defaultAddress.state }} {{ defaultAddress.postal_code }}</p>
            <p>{{ defaultAddress.country }}</p>
          </div>
          <div v-else class="py-4 text-xs text-ink-muted">
            No default shipping address saved yet. Save an address for faster 1-click checkout.
          </div>
        </div>

        <div class="mt-5 pt-4 border-t border-line flex items-center justify-between text-xs">
          <span class="text-ink-muted">Saved shipping & billing locations</span>
          <RouterLink
            to="/account/addresses"
            class="inline-flex items-center gap-1 font-semibold text-ink hover:text-brand transition"
          >
            <span>Add / Edit</span>
            <ArrowRight class="w-3.5 h-3.5" />
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
      <div class="flex items-center justify-between mb-5">
        <div>
          <h2 class="text-base font-bold text-ink">Recent Orders</h2>
          <p class="text-xs text-ink-muted">Track current shipments or reorder past favorites</p>
        </div>
        <RouterLink
          to="/account/orders"
          class="inline-flex items-center gap-1 text-xs font-semibold text-brand hover:underline"
        >
          <span>View All Orders</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </RouterLink>
      </div>

      <div v-if="loading" class="space-y-3">
        <div v-for="i in 2" :key="i" class="h-20 rounded-xl bg-surface animate-pulse" />
      </div>

      <div v-else-if="recentOrders.length === 0" class="text-center py-10">
        <div class="w-12 h-12 rounded-full bg-surface text-ink-muted flex items-center justify-center mx-auto mb-3">
          <Package class="w-5 h-5" />
        </div>
        <p class="text-sm font-semibold text-ink">You haven't placed any orders yet</p>
        <p class="text-xs text-ink-muted mt-1 mb-4">When you place an order, its status and tracking will appear here.</p>
        <RouterLink
          to="/shop"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
        >
          <span>Explore Collection</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </RouterLink>
      </div>

      <div v-else class="divide-y divide-line">
        <div
          v-for="order in recentOrders"
          :key="order.id"
          class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
        >
          <div class="space-y-1">
            <div class="flex items-center gap-2.5 flex-wrap">
              <RouterLink
                :to="`/account/orders/${order.order_number}`"
                class="text-sm font-bold text-ink hover:text-brand transition font-mono"
              >
                {{ order.order_number }}
              </RouterLink>
              <span
                class="px-2.5 py-0.5 rounded-pill text-[11px] font-semibold border capitalize"
                :class="statusBadgeClass(order.status)"
              >
                {{ order.status }}
              </span>
            </div>
            <p class="text-xs text-ink-muted">
              Placed on {{ order.placed_at }} · {{ order.item_count }} {{ order.item_count === 1 ? 'item' : 'items' }}
            </p>
          </div>

          <div class="flex items-center justify-between sm:justify-end gap-4">
            <div class="text-left sm:text-right">
              <p class="text-sm font-bold text-ink">
                {{ settingsStore.formatPrice(order.grand_total) }}
              </p>
              <p class="text-[11px] text-ink-muted capitalize">
                Payment: {{ order.payment_status }}
              </p>
            </div>

            <RouterLink
              :to="`/account/orders/${order.order_number}`"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line text-xs font-semibold text-ink hover:border-brand hover:text-brand transition"
            >
              <span>View Order</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
