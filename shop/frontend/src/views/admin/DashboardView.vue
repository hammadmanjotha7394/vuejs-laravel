<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useHead } from '@unhead/vue'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import DataTable from '@/components/admin/DataTable.vue'
import {
  ShoppingBag,
  Users,
  AlertTriangle,
  MessageSquare,
  Star,
  CircleDollarSign,
  TrendingUp,
  Package,
  ArrowRight,
  RefreshCw,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const settingsStore = useSettingsStore()

useHead({
  title: () => `Admin Dashboard | ${settingsStore.siteName}`,
})

const loading = ref(true)
const cards = ref({
  todays_orders: 0,
  new_customers: 0,
  low_stock_products: 0,
  open_chats: 0,
  pending_reviews: 0,
})
const revenue = ref(null)
const charts = ref({
  sales_last_30_days: [],
  top_products: [],
  orders_by_status: [],
})
const latestOrders = ref([])
const lowStockItems = ref([])
const hoveredBarIndex = ref(null)

const canViewFinance = computed(() => authStore.hasPermission('finance.view'))
const canViewOrders = computed(
  () => authStore.hasPermission('orders.view') || canViewFinance.value
)
const canViewProducts = computed(() => authStore.hasPermission('products.view'))

const fetchDashboard = async () => {
  loading.value = true
  try {
    const res = await apiClient.get('/admin/dashboard')
    const data = res.data?.data || {}
    cards.value = data.cards || cards.value
    revenue.value = data.revenue || null
    charts.value = data.charts || charts.value
    latestOrders.value = data.latest_orders || []
    lowStockItems.value = data.low_stock_items || []
  } catch (e) {
    // Handled gracefully
  } finally {
    loading.value = false
  }
}

// Compute max value for 30-day SVG chart scaling
const chartMetricKey = computed(() =>
  canViewFinance.value && revenue.value ? 'revenue' : 'orders_count'
)

const maxSalesPoint = computed(() => {
  const points = charts.value.sales_last_30_days || []
  if (!points.length) return 1
  const maxVal = Math.max(...points.map((p) => Number(p[chartMetricKey.value] || 0)))
  return maxVal > 0 ? maxVal : 1
})

const maxTopProductUnits = computed(() => {
  const list = charts.value.top_products || []
  if (!list.length) return 1
  const maxVal = Math.max(...list.map((p) => Number(p.units_sold || 0)))
  return maxVal > 0 ? maxVal : 1
})

const totalStatusOrders = computed(() => {
  const list = charts.value.orders_by_status || []
  const sum = list.reduce((acc, s) => acc + Number(s.count || 0), 0)
  return sum > 0 ? sum : 1
})

const statusColorMap = {
  pending: 'bg-amber-400',
  paid: 'bg-emerald-500',
  processing: 'bg-blue-500',
  shipped: 'bg-indigo-500',
  delivered: 'bg-brand',
  cancelled: 'bg-rose-400',
  refunded: 'bg-gray-400',
}

const statusPillClass = (status) => {
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

const orderColumns = [
  { key: 'order_number', label: 'Order #', sortable: false },
  { key: 'customer_name', label: 'Customer', sortable: false },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'item_count', label: 'Items', align: 'center' },
  { key: 'grand_total', label: 'Total', align: 'right' },
  { key: 'placed_at', label: 'Placed At', align: 'right' },
]

onMounted(() => {
  fetchDashboard()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Top Welcome Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-ink tracking-tight">
          Store Overview
        </h1>
        <p class="text-xs text-ink-muted mt-0.5">
          Welcome back, <strong class="text-ink">{{ authStore.user?.name }}</strong> · Real-time store activity and performance
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <button
          type="button"
          :disabled="loading"
          @click="fetchDashboard"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-white text-xs font-semibold text-ink hover:border-brand hover:text-brand transition"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
          <span>Refresh</span>
        </button>

        <RouterLink
          v-if="canViewProducts"
          to="/admin/products/create"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
        >
          <Package class="w-3.5 h-3.5" />
          <span>New Product</span>
        </RouterLink>
      </div>
    </div>

    <!-- Revenue KPI Row (Strictly rendered ONLY for users with finance.view) -->
    <div
      v-if="canViewFinance && revenue"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
    >
      <div class="bg-white rounded-card border border-line p-5 shadow-card">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold text-ink-muted">Today's Revenue</span>
          <div class="w-8 h-8 rounded-xl bg-brand/10 text-brand flex items-center justify-center">
            <CircleDollarSign class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl font-bold text-ink">
          {{ settingsStore.formatPrice(revenue.today_revenue) }}
        </p>
        <p class="text-[11px] text-ink-muted mt-1">Confirmed paid orders today</p>
      </div>

      <div class="bg-white rounded-card border border-line p-5 shadow-card">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold text-ink-muted">30-Day Revenue</span>
          <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
            <TrendingUp class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl font-bold text-ink">
          {{ settingsStore.formatPrice(revenue.month_revenue) }}
        </p>
        <p class="text-[11px] text-ink-muted mt-1">Rolling 30-day gross sales</p>
      </div>

      <div class="bg-white rounded-card border border-line p-5 shadow-card">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold text-ink-muted">Average Order Value</span>
          <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
            <ShoppingBag class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl font-bold text-ink">
          {{ settingsStore.formatPrice(revenue.avg_order_value) }}
        </p>
        <p class="text-[11px] text-ink-muted mt-1">Per completed checkout</p>
      </div>

      <div class="bg-white rounded-card border border-line p-5 shadow-card">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold text-ink-muted">Cumulative Revenue</span>
          <div class="w-8 h-8 rounded-xl bg-brand/10 text-brand flex items-center justify-center">
            <CircleDollarSign class="w-4 h-4" />
          </div>
        </div>
        <p class="text-2xl font-bold text-ink">
          {{ settingsStore.formatPrice(revenue.total_revenue) }}
        </p>
        <RouterLink
          to="/admin/finance"
          class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand hover:underline mt-1"
        >
          <span>Full Finance Report</span>
          <ArrowRight class="w-3 h-3" />
        </RouterLink>
      </div>
    </div>

    <!-- Operational KPI Cards (Visible to all staff) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
      <div class="bg-white rounded-card border border-line p-4 sm:p-5 shadow-card">
        <div class="w-8 h-8 rounded-xl bg-brand/10 text-brand flex items-center justify-center mb-2.5">
          <ShoppingBag class="w-4 h-4" />
        </div>
        <p class="text-xl sm:text-2xl font-bold text-ink">
          {{ loading ? '—' : cards.todays_orders }}
        </p>
        <p class="text-xs font-medium text-ink-muted mt-0.5">Today's Orders</p>
      </div>

      <div class="bg-white rounded-card border border-line p-4 sm:p-5 shadow-card">
        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center mb-2.5">
          <Users class="w-4 h-4" />
        </div>
        <p class="text-xl sm:text-2xl font-bold text-ink">
          {{ loading ? '—' : cards.new_customers }}
        </p>
        <p class="text-xs font-medium text-ink-muted mt-0.5">New Customers (30d)</p>
      </div>

      <div class="bg-white rounded-card border border-line p-4 sm:p-5 shadow-card">
        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center mb-2.5">
          <AlertTriangle class="w-4 h-4" />
        </div>
        <p class="text-xl sm:text-2xl font-bold" :class="cards.low_stock_products > 0 ? 'text-amber-700' : 'text-ink'">
          {{ loading ? '—' : cards.low_stock_products }}
        </p>
        <p class="text-xs font-medium text-ink-muted mt-0.5">Low-Stock Products</p>
      </div>

      <div class="bg-white rounded-card border border-line p-4 sm:p-5 shadow-card">
        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center mb-2.5">
          <MessageSquare class="w-4 h-4" />
        </div>
        <p class="text-xl sm:text-2xl font-bold text-ink">
          {{ loading ? '—' : cards.open_chats }}
        </p>
        <p class="text-xs font-medium text-ink-muted mt-0.5">Open Chats</p>
      </div>

      <div class="bg-white rounded-card border border-line p-4 sm:p-5 shadow-card col-span-2 sm:col-span-1">
        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mb-2.5">
          <Star class="w-4 h-4" />
        </div>
        <p class="text-xl sm:text-2xl font-bold text-ink">
          {{ loading ? '—' : cards.pending_reviews }}
        </p>
        <p class="text-xs font-medium text-ink-muted mt-0.5">Pending Reviews</p>
      </div>
    </div>

    <!-- Lightweight Charts Row: Sales Last 30 Days + Orders by Status -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- 1. Sales Last 30 Days (Lightweight Responsive SVG Bar Chart) -->
      <div class="lg:col-span-8 bg-white rounded-card border border-line p-5 sm:p-6 shadow-card flex flex-col justify-between">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
          <div>
            <h2 class="text-base font-bold text-ink">
              {{ canViewFinance ? 'Sales Last 30 Days' : 'Order Volume (Last 30 Days)' }}
            </h2>
            <p class="text-xs text-ink-muted">
              {{ canViewFinance ? 'Daily confirmed revenue and order activity' : 'Daily orders placed across the storefront' }}
            </p>
          </div>

          <div
            v-if="hoveredBarIndex !== null && charts.sales_last_30_days[hoveredBarIndex]"
            class="px-3 py-1 rounded-pill bg-surface border border-line text-xs font-semibold text-ink"
          >
            <span>{{ charts.sales_last_30_days[hoveredBarIndex].label }}: </span>
            <span v-if="canViewFinance && charts.sales_last_30_days[hoveredBarIndex].revenue !== undefined" class="text-brand font-bold">
              {{ settingsStore.formatPrice(charts.sales_last_30_days[hoveredBarIndex].revenue) }}
              ({{ charts.sales_last_30_days[hoveredBarIndex].orders_count }} orders)
            </span>
            <span v-else class="text-brand font-bold">
              {{ charts.sales_last_30_days[hoveredBarIndex].orders_count }} orders
            </span>
          </div>
        </div>

        <!-- SVG Bar Chart -->
        <div class="h-48 flex items-end gap-1 sm:gap-1.5 pt-4 border-b border-line pb-2">
          <div
            v-for="(pt, idx) in charts.sales_last_30_days"
            :key="pt.date"
            class="flex-1 h-full flex flex-col justify-end items-center group cursor-pointer"
            @mouseenter="hoveredBarIndex = idx"
            @mouseleave="hoveredBarIndex = null"
          >
            <div
              class="w-full rounded-t-md transition-all duration-150"
              :class="
                hoveredBarIndex === idx
                  ? 'bg-brand'
                  : Number(pt[chartMetricKey]) > 0
                    ? 'bg-brand/75'
                    : 'bg-surface'
              "
              :style="{
                height: `${Math.max(6, Math.round((Number(pt[chartMetricKey] || 0) / maxSalesPoint) * 100))}%`,
              }"
            />
          </div>
        </div>

        <div class="flex items-center justify-between text-[11px] text-ink-muted pt-2">
          <span>{{ charts.sales_last_30_days[0]?.label || '30 days ago' }}</span>
          <span>{{ charts.sales_last_30_days[14]?.label || '15 days ago' }}</span>
          <span>Today ({{ charts.sales_last_30_days[charts.sales_last_30_days.length - 1]?.label || 'Now' }})</span>
        </div>
      </div>

      <!-- 2. Orders by Status Breakdown -->
      <div class="lg:col-span-4 bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
        <div class="mb-4">
          <h2 class="text-base font-bold text-ink">Orders by Status</h2>
          <p class="text-xs text-ink-muted">Fulfillment pipeline distribution</p>
        </div>

        <div class="space-y-3">
          <div
            v-for="item in charts.orders_by_status"
            :key="item.status"
            class="space-y-1 text-xs"
          >
            <div class="flex items-center justify-between">
              <span class="font-semibold text-ink capitalize">{{ item.status }}</span>
              <span class="text-ink-muted font-mono">{{ item.count }}</span>
            </div>
            <div class="h-2 rounded-full bg-surface overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="statusColorMap[item.status] || 'bg-brand'"
                :style="{
                  width: `${item.count > 0 ? Math.max(6, Math.round((item.count / totalStatusOrders) * 100)) : 0}%`,
                }"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Products & Low-Stock Inventory Row -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Top Products Chart -->
      <div class="lg:col-span-6 bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-base font-bold text-ink">Top Products</h2>
            <p class="text-xs text-ink-muted">Best-performing catalog items by units sold</p>
          </div>
          <RouterLink
            v-if="canViewProducts"
            to="/admin/products"
            class="text-xs font-semibold text-brand hover:underline"
          >
            View Catalog
          </RouterLink>
        </div>

        <div v-if="!charts.top_products?.length" class="py-8 text-center text-xs text-ink-muted">
          No product sales recorded yet.
        </div>

        <div v-else class="space-y-3.5">
          <div
            v-for="(prod, i) in charts.top_products"
            :key="prod.product_id || i"
            class="space-y-1.5 text-xs"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="font-semibold text-ink truncate">
                {{ i + 1 }}. {{ prod.name }}
              </span>
              <div class="flex items-center gap-2 shrink-0">
                <span class="text-ink-muted">{{ prod.units_sold }} sold</span>
                <span
                  v-if="canViewFinance && prod.revenue !== undefined"
                  class="font-bold text-ink"
                >
                  · {{ settingsStore.formatPrice(prod.revenue) }}
                </span>
              </div>
            </div>
            <div class="h-2 rounded-full bg-surface overflow-hidden">
              <div
                class="h-full rounded-full bg-brand transition-all duration-300"
                :style="{
                  width: `${prod.units_sold > 0 ? Math.max(8, Math.round((prod.units_sold / maxTopProductUnits) * 100)) : 4}%`,
                }"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Low-Stock Alert List -->
      <div class="lg:col-span-6 bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-base font-bold text-ink">Low-Stock Products</h2>
            <p class="text-xs text-ink-muted">Items at or below their restock threshold</p>
          </div>
          <RouterLink
            v-if="canViewProducts"
            to="/admin/products"
            class="text-xs font-semibold text-brand hover:underline"
          >
            Manage Stock
          </RouterLink>
        </div>

        <div v-if="!lowStockItems.length" class="py-8 text-center text-xs text-ink-muted">
          All active products are well stocked above their threshold.
        </div>

        <div v-else class="divide-y divide-line">
          <div
            v-for="item in lowStockItems"
            :key="item.id"
            class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-3 text-xs"
          >
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-10 h-10 rounded-xl bg-surface border border-line overflow-hidden shrink-0 flex items-center justify-center">
                <img
                  v-if="item.image"
                  :src="item.image"
                  :alt="item.name"
                  class="w-full h-full object-cover"
                />
                <Package v-else class="w-4 h-4 text-ink-muted" />
              </div>
              <div class="min-w-0">
                <p class="font-bold text-ink truncate">{{ item.name }}</p>
                <p class="text-[11px] text-ink-muted font-mono">SKU: {{ item.sku || 'N/A' }}</p>
              </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
              <span
                class="px-2.5 py-0.5 rounded-pill font-bold text-[11px]"
                :class="
                  item.stock <= 0
                    ? 'bg-red-50 text-error border border-red-200'
                    : 'bg-amber-50 text-amber-800 border border-amber-200'
                "
              >
                {{ item.stock }} left
              </span>
              <RouterLink
                v-if="authStore.hasPermission('products.update')"
                :to="`/admin/products/${item.id}/edit`"
                class="text-xs font-semibold text-brand hover:underline"
              >
                Edit
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Latest Orders Table (Responsive DataTable) -->
    <div v-if="canViewOrders" class="space-y-3">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-ink">Latest Orders</h2>
          <p class="text-xs text-ink-muted">Most recent customer checkouts</p>
        </div>
        <RouterLink
          to="/admin/orders"
          class="inline-flex items-center gap-1 text-xs font-semibold text-brand hover:underline"
        >
          <span>View All Orders</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </RouterLink>
      </div>

      <DataTable
        :columns="orderColumns"
        :rows="latestOrders"
        :loading="loading"
        :searchable="false"
        empty-title="No orders placed yet"
        empty-description="Incoming customer orders will appear here in real time."
      >
        <template #cell-order_number="{ row }">
          <span class="font-mono font-bold text-ink">{{ row.order_number }}</span>
        </template>

        <template #cell-customer_name="{ row }">
          <div>
            <p class="font-semibold text-ink">{{ row.customer_name }}</p>
            <p class="text-[11px] text-ink-muted">{{ row.customer_email }}</p>
          </div>
        </template>

        <template #cell-status="{ row }">
          <span
            class="px-2.5 py-0.5 rounded-pill text-[11px] font-semibold border capitalize"
            :class="statusPillClass(row.status)"
          >
            {{ row.status }}
          </span>
        </template>

        <template #cell-grand_total="{ row }">
          <span class="font-bold text-ink">
            {{ settingsStore.formatPrice(row.grand_total) }}
          </span>
        </template>
      </DataTable>
    </div>
  </div>
</template>
