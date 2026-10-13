<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  ShoppingBag,
  Search,
  RotateCcw,
  Eye,
  Truck,
  AlertTriangle,
  Mail,
  Save,
  CheckCircle2,
  Clock,
  XCircle,
  Package,
  Calendar,
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import DataTable from '@/components/admin/DataTable.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const activeTab = ref('orders') // 'orders' | 'inventory'
const orders = ref([])
const summary = ref({
  total_orders: 0,
  unfulfilled_count: 0,
  shipped_count: 0,
  refunded_count: 0,
  low_stock_count: 0,
})
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})
const isLoading = ref(false)
const toastMessage = ref('')
const errorMessage = ref('')

const filters = reactive({
  search: '',
  status: '',
  payment_status: '',
  date_from: '',
  date_to: '',
  sort_by: 'created_at',
  sort_dir: 'desc',
})

// Low-stock inventory state
const lowStockItems = ref([])
const isLoadingInventory = ref(false)
const inventoryFilter = ref('low') // 'low' | 'out'
const inventorySearch = ref('')
const alertEmail = ref('')
const isSendingAlert = ref(false)
const savingStockId = ref(null)

const orderColumns = [
  { key: 'order_number', label: 'Order', sortable: true },
  { key: 'created_at', label: 'Date', sortable: true },
  { key: 'customer', label: 'Customer' },
  { key: 'items_count', label: 'Items' },
  { key: 'payment_status', label: 'Payment', sortable: true },
  { key: 'status', label: 'Fulfillment Status', sortable: true },
  { key: 'grand_total', label: 'Total', sortable: true, align: 'right' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const showToast = (msg) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = ''
  }, 3500)
}

const fetchOrders = async (page = 1) => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      sort_by: filters.sort_by,
      sort_dir: filters.sort_dir,
    }
    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    if (filters.payment_status) params.payment_status = filters.payment_status
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const res = await apiClient.get('/admin/orders', { params })
    orders.value = res.data.data || []
    pagination.value = {
      current_page: res.data.current_page || 1,
      last_page: res.data.last_page || 1,
      per_page: res.data.per_page || 20,
      total: res.data.total || 0,
    }
    if (res.data.summary) {
      summary.value = res.data.summary
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Unable to load orders.'
  } finally {
    isLoading.value = false
  }
}

const fetchLowStock = async () => {
  isLoadingInventory.value = true
  try {
    const res = await apiClient.get('/admin/inventory/low-stock', {
      params: {
        filter: inventoryFilter.value,
        search: inventorySearch.value || undefined,
      },
    })
    lowStockItems.value = (res.data.data || []).map((p) => ({
      ...p,
      edit_stock_qty: p.stock_qty,
      edit_threshold: p.low_stock_threshold ?? 5,
    }))
    if (res.data.alert_email && !alertEmail.value) {
      alertEmail.value = res.data.alert_email
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Unable to load low-stock inventory.'
  } finally {
    isLoadingInventory.value = false
  }
}

const saveProductStock = async (item) => {
  savingStockId.value = item.id
  try {
    await apiClient.patch('/admin/inventory/stock', {
      product_id: item.id,
      stock_qty: Number(item.edit_stock_qty),
      low_stock_threshold: Number(item.edit_threshold),
    })
    item.stock_qty = Number(item.edit_stock_qty)
    item.low_stock_threshold = Number(item.edit_threshold)
    showToast(`Stock updated for ${item.name}.`)
    fetchLowStock()
    fetchOrders(pagination.value.current_page)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update stock.'
  } finally {
    savingStockId.value = null
  }
}

const sendLowStockAlertEmail = async () => {
  isSendingAlert.value = true
  errorMessage.value = ''
  try {
    const res = await apiClient.post('/admin/inventory/low-stock-alert', {
      email: alertEmail.value || undefined,
    })
    showToast(res.data.message || 'Low-stock alert email sent.')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to send low-stock alert email.'
  } finally {
    isSendingAlert.value = false
  }
}

const handleSort = ({ key, dir }) => {
  filters.sort_by = key
  filters.sort_dir = dir
  fetchOrders(1)
}

const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.payment_status = ''
  filters.date_from = ''
  filters.date_to = ''
  filters.sort_by = 'created_at'
  filters.sort_dir = 'desc'
  fetchOrders(1)
}

const statusBadgeClass = (status) => {
  const map = {
    pending: 'bg-amber-50 text-amber-800 border-amber-200',
    paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    processing: 'bg-blue-50 text-blue-700 border-blue-200',
    shipped: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    delivered: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    cancelled: 'bg-gray-100 text-gray-600 border-gray-200',
    refunded: 'bg-red-50 text-red-700 border-red-200',
  }
  return map[status] || 'bg-surface text-ink-muted border-line'
}

const paymentBadgeClass = (status) => {
  const map = {
    paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    pending: 'bg-amber-50 text-amber-800 border-amber-200',
    partially_refunded: 'bg-orange-50 text-orange-700 border-orange-200',
    refunded: 'bg-red-50 text-red-700 border-red-200',
    expired: 'bg-gray-100 text-gray-600 border-gray-200',
    failed: 'bg-red-50 text-red-700 border-red-200',
  }
  return map[status] || 'bg-surface text-ink-muted border-line'
}

const formatDate = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

let searchTimer = null
watch(
  () => filters.search,
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => fetchOrders(1), 280)
  }
)

watch([() => filters.status, () => filters.payment_status, () => filters.date_from, () => filters.date_to], () => {
  fetchOrders(1)
})

watch([inventoryFilter, inventorySearch], () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => fetchLowStock(), 250)
})

onMounted(() => {
  fetchOrders(1)
  fetchLowStock()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Orders & Inventory</h1>
        <p class="text-xs text-ink-muted mt-1">
          Manage customer orders, update fulfillment status, add tracking numbers, issue Stripe refunds, and monitor low-stock inventory.
        </p>
      </div>

      <!-- Mode Switch Tabs -->
      <div class="inline-flex items-center bg-surface border border-line rounded-pill p-1 self-start">
        <button
          type="button"
          @click="activeTab = 'orders'"
          class="inline-flex items-center gap-2 px-4 py-1.5 rounded-pill text-xs font-semibold transition"
          :class="activeTab === 'orders' ? 'bg-page text-ink shadow-sm' : 'text-ink-muted hover:text-ink'"
        >
          <ShoppingBag class="w-3.5 h-3.5" />
          <span>Orders ({{ summary.total_orders }})</span>
        </button>
        <button
          type="button"
          @click="activeTab = 'inventory'"
          class="inline-flex items-center gap-2 px-4 py-1.5 rounded-pill text-xs font-semibold transition"
          :class="activeTab === 'inventory' ? 'bg-page text-ink shadow-sm' : 'text-ink-muted hover:text-ink'"
        >
          <AlertTriangle class="w-3.5 h-3.5" :class="summary.low_stock_count > 0 ? 'text-error' : ''" />
          <span>Low-Stock Inventory</span>
          <span
            v-if="summary.low_stock_count > 0"
            class="px-1.5 py-0.5 rounded-pill text-[10px] font-bold bg-red-100 text-error"
          >
            {{ summary.low_stock_count }}
          </span>
        </button>
      </div>
    </div>

    <!-- Toasts / Alerts -->
    <div
      v-if="toastMessage"
      class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-success px-4 py-3 rounded-card text-xs font-medium"
    >
      <CheckCircle2 class="w-4 h-4 shrink-0" />
      <span>{{ toastMessage }}</span>
    </div>

    <div
      v-if="errorMessage"
      class="flex items-center justify-between bg-red-50 border border-red-200 text-error px-4 py-3 rounded-card text-xs font-medium"
    >
      <span>{{ errorMessage }}</span>
      <button @click="errorMessage = ''" class="text-[11px] underline">Dismiss</button>
    </div>

    <!-- TAB 1: ORDERS LIST -->
    <div v-if="activeTab === 'orders'" class="space-y-5">
      <!-- Quick Status Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <button
          type="button"
          @click="filters.status = ''"
          class="text-left bg-page border rounded-card p-4 shadow-soft transition hover:border-brand"
          :class="filters.status === '' ? 'border-brand ring-1 ring-brand/20' : 'border-line'"
        >
          <div class="flex items-center justify-between text-ink-muted text-xs font-medium">
            <span>All Orders</span>
            <ShoppingBag class="w-4 h-4 text-brand" />
          </div>
          <p class="text-2xl font-bold text-ink mt-1.5">{{ summary.total_orders }}</p>
        </button>

        <button
          type="button"
          @click="filters.status = 'paid'"
          class="text-left bg-page border rounded-card p-4 shadow-soft transition hover:border-brand"
          :class="filters.status === 'paid' ? 'border-brand ring-1 ring-brand/20' : 'border-line'"
        >
          <div class="flex items-center justify-between text-ink-muted text-xs font-medium">
            <span>Ready to Ship</span>
            <Clock class="w-4 h-4 text-amber-600" />
          </div>
          <p class="text-2xl font-bold text-ink mt-1.5">{{ summary.unfulfilled_count }}</p>
        </button>

        <button
          type="button"
          @click="filters.status = 'shipped'"
          class="text-left bg-page border rounded-card p-4 shadow-soft transition hover:border-brand"
          :class="filters.status === 'shipped' ? 'border-brand ring-1 ring-brand/20' : 'border-line'"
        >
          <div class="flex items-center justify-between text-ink-muted text-xs font-medium">
            <span>Shipped</span>
            <Truck class="w-4 h-4 text-indigo-600" />
          </div>
          <p class="text-2xl font-bold text-ink mt-1.5">{{ summary.shipped_count }}</p>
        </button>

        <button
          type="button"
          @click="filters.status = 'refunded'"
          class="text-left bg-page border rounded-card p-4 shadow-soft transition hover:border-brand"
          :class="filters.status === 'refunded' ? 'border-brand ring-1 ring-brand/20' : 'border-line'"
        >
          <div class="flex items-center justify-between text-ink-muted text-xs font-medium">
            <span>Refunded</span>
            <XCircle class="w-4 h-4 text-error" />
          </div>
          <p class="text-2xl font-bold text-ink mt-1.5">{{ summary.refunded_count }}</p>
        </button>
      </div>

      <!-- DataTable -->
      <DataTable
        :columns="orderColumns"
        :rows="orders"
        :loading="isLoading"
        :pagination="pagination"
        :sort-by="filters.sort_by"
        :sort-dir="filters.sort_dir"
        empty-title="No orders match your filters"
        empty-description="Try clearing your search query or adjusting the date and status filters."
        @sort="handleSort"
        @page-change="fetchOrders"
      >
        <template #filters>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 w-full">
            <!-- Search -->
            <div class="relative lg:col-span-2">
              <Search class="w-4 h-4 text-ink-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search order #, customer email, tracking..."
                class="w-full pl-9 pr-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <!-- Order Status -->
            <select
              v-model="filters.status"
              class="px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
            >
              <option value="">All Order Statuses</option>
              <option value="pending">Pending</option>
              <option value="paid">Paid</option>
              <option value="processing">Processing</option>
              <option value="shipped">Shipped</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
              <option value="refunded">Refunded</option>
            </select>

            <!-- Payment Status -->
            <select
              v-model="filters.payment_status"
              class="px-3 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
            >
              <option value="">All Payment Statuses</option>
              <option value="paid">Paid</option>
              <option value="pending">Pending</option>
              <option value="partially_refunded">Partially Refunded</option>
              <option value="refunded">Refunded</option>
              <option value="expired">Expired</option>
            </select>

            <!-- Date From -->
            <div class="relative">
              <Calendar class="w-3.5 h-3.5 text-ink-muted absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
              <input
                v-model="filters.date_from"
                type="date"
                title="From Date"
                class="w-full pl-8 pr-2.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              />
            </div>

            <!-- Date To + Reset -->
            <div class="flex items-center gap-2">
              <input
                v-model="filters.date_to"
                type="date"
                title="To Date"
                class="w-full px-2.5 py-2 text-xs bg-surface border border-line rounded-xl text-ink focus:outline-none focus:border-brand"
              />
              <button
                type="button"
                @click="resetFilters"
                title="Reset Filters"
                class="p-2 rounded-xl border border-line bg-surface text-ink-muted hover:text-ink transition shrink-0"
              >
                <RotateCcw class="w-4 h-4" />
              </button>
            </div>
          </div>
        </template>

        <template #cell-order_number="{ row }">
          <router-link
            :to="`/admin/orders/${row.id}`"
            class="font-mono text-xs font-bold text-brand hover:underline"
          >
            {{ row.order_number }}
          </router-link>
          <div v-if="row.tracking_number" class="flex items-center gap-1 text-[11px] text-ink-muted mt-0.5">
            <Truck class="w-3 h-3 text-indigo-600" />
            <span class="font-mono">{{ row.tracking_number }}</span>
          </div>
        </template>

        <template #cell-created_at="{ row }">
          <span class="text-xs text-ink-muted whitespace-nowrap">
            {{ formatDate(row.placed_at || row.created_at) }}
          </span>
        </template>

        <template #cell-customer="{ row }">
          <div class="min-w-0">
            <div class="flex items-center gap-1.5">
              <span class="text-xs font-semibold text-ink truncate">
                {{ row.user?.name || (row.shipping_address_json ? `${row.shipping_address_json.first_name || ''} ${row.shipping_address_json.last_name || ''}`.trim() : 'Guest Customer') }}
              </span>
              <span
                v-if="!row.user_id"
                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-surface text-ink-muted border border-line"
              >
                Guest
              </span>
            </div>
            <p class="text-[11px] text-ink-muted truncate">
              {{ row.guest_email || row.user?.email || '—' }}
            </p>
          </div>
        </template>

        <template #cell-items_count="{ row }">
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-pill text-xs font-medium bg-surface text-ink border border-line">
            <Package class="w-3 h-3 text-ink-muted" />
            <span>{{ row.items_count || 0 }}</span>
          </span>
        </template>

        <template #cell-payment_status="{ row }">
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-pill text-[11px] font-semibold capitalize border"
            :class="paymentBadgeClass(row.payment_status)"
          >
            {{ (row.payment_status || 'pending').replace('_', ' ') }}
          </span>
        </template>

        <template #cell-status="{ row }">
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-pill text-[11px] font-semibold capitalize border"
            :class="statusBadgeClass(row.status)"
          >
            {{ row.status }}
          </span>
        </template>

        <template #cell-grand_total="{ row }">
          <div class="text-right">
            <span class="text-xs font-bold text-ink">${{ Number(row.grand_total).toFixed(2) }}</span>
            <p v-if="Number(row.refunded_amount) > 0" class="text-[10px] text-error font-medium">
              -${{ Number(row.refunded_amount).toFixed(2) }} refunded
            </p>
          </div>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-2">
            <button
              type="button"
              @click="router.push(`/admin/orders/${row.id}`)"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:border-brand hover:text-brand transition"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>Manage</span>
            </button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- TAB 2: LOW-STOCK INVENTORY & ALERT DISPATCH -->
    <div v-else class="space-y-5">
      <!-- Low-Stock Email Alert Bar -->
      <div class="bg-page border border-line rounded-card p-5 shadow-soft flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h2 class="text-sm font-bold text-ink flex items-center gap-2">
            <AlertTriangle class="w-4 h-4 text-error" />
            <span>Low-Stock Inventory Watchlist</span>
          </h2>
          <p class="text-xs text-ink-muted mt-0.5">
            Quickly adjust product inventory quantities inline or send a low-stock summary alert email to your operations inbox.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
          <div class="relative">
            <Mail class="w-3.5 h-3.5 text-ink-muted absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="alertEmail"
              type="email"
              placeholder="Alert email address"
              class="pl-8 pr-3 py-2 text-xs bg-surface border border-line rounded-pill text-ink focus:outline-none focus:border-brand w-full sm:w-60"
            />
          </div>
          <button
            type="button"
            @click="sendLowStockAlertEmail"
            :disabled="isSendingAlert"
            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition disabled:opacity-50"
          >
            <Mail class="w-3.5 h-3.5" />
            <span>{{ isSendingAlert ? 'Sending Alert...' : 'Send Low-Stock Email Alert' }}</span>
          </button>
        </div>
      </div>

      <!-- Inventory Filter Bar -->
      <div class="bg-page border border-line rounded-card p-5 shadow-soft space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="inventoryFilter = 'low'"
              class="px-3.5 py-1.5 rounded-pill text-xs font-semibold border transition"
              :class="inventoryFilter === 'low' ? 'bg-brand text-white border-brand' : 'bg-surface text-ink border-line'"
            >
              Low & Out of Stock
            </button>
            <button
              type="button"
              @click="inventoryFilter = 'out'"
              class="px-3.5 py-1.5 rounded-pill text-xs font-semibold border transition"
              :class="inventoryFilter === 'out' ? 'bg-error text-white border-error' : 'bg-surface text-ink border-line'"
            >
              Out of Stock Only (0)
            </button>
          </div>

          <div class="relative w-full sm:w-72">
            <Search class="w-3.5 h-3.5 text-ink-muted absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="inventorySearch"
              type="text"
              placeholder="Filter low-stock by name or SKU..."
              class="w-full pl-8 pr-3 py-1.5 text-xs bg-surface border border-line rounded-pill text-ink focus:outline-none focus:border-brand"
            />
          </div>
        </div>

        <div v-if="isLoadingInventory" class="py-12 text-center text-xs text-ink-muted">
          Loading low-stock inventory...
        </div>

        <div v-else-if="lowStockItems.length === 0" class="py-12 text-center">
          <CheckCircle2 class="w-8 h-8 text-success mx-auto mb-2" />
          <p class="text-sm font-semibold text-ink">All tracked products are well-stocked</p>
          <p class="text-xs text-ink-muted mt-0.5">No products are currently below their low-stock threshold.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-xs text-left">
            <thead class="border-b border-line text-ink-muted font-semibold uppercase tracking-wider">
              <tr>
                <th class="py-3 px-3">Product</th>
                <th class="py-3 px-3">SKU</th>
                <th class="py-3 px-3">Category</th>
                <th class="py-3 px-3">Current Status</th>
                <th class="py-3 px-3">Alert Threshold</th>
                <th class="py-3 px-3">Stock Quantity</th>
                <th class="py-3 px-3 text-right">Quick Save</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-line">
              <tr v-for="item in lowStockItems" :key="item.id" class="hover:bg-surface/60 transition">
                <td class="py-3 px-3">
                  <div class="flex items-center gap-3">
                    <img
                      v-if="item.primary_image?.url || item.primary_image?.thumb_url"
                      :src="item.primary_image.thumb_url || item.primary_image.url"
                      :alt="item.name"
                      class="w-10 h-10 rounded-lg object-cover border border-line bg-surface shrink-0"
                    />
                    <div class="min-w-0">
                      <router-link
                        :to="`/admin/products/${item.id}/edit`"
                        class="font-semibold text-ink hover:text-brand truncate block"
                      >
                        {{ item.name }}
                      </router-link>
                      <span class="text-[11px] text-ink-muted">{{ item.brand?.name || 'Boutique' }}</span>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3 font-mono text-ink-muted">{{ item.sku || '—' }}</td>
                <td class="py-3 px-3 text-ink-muted">{{ item.category?.name || '—' }}</td>
                <td class="py-3 px-3">
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-pill text-[11px] font-semibold border"
                    :class="item.stock_qty <= 0 ? 'bg-red-50 text-error border-red-200' : 'bg-amber-50 text-amber-800 border-amber-200'"
                  >
                    {{ item.stock_qty <= 0 ? 'Out of Stock' : `Low (${item.stock_qty} left)` }}
                  </span>
                </td>
                <td class="py-3 px-3">
                  <input
                    v-model.number="item.edit_threshold"
                    type="number"
                    min="0"
                    class="w-20 px-2.5 py-1.5 text-xs bg-surface border border-line rounded-lg text-ink focus:outline-none focus:border-brand"
                  />
                </td>
                <td class="py-3 px-3">
                  <input
                    v-model.number="item.edit_stock_qty"
                    type="number"
                    min="0"
                    class="w-24 px-2.5 py-1.5 text-xs font-bold bg-surface border border-line rounded-lg text-ink focus:outline-none focus:border-brand"
                  />
                </td>
                <td class="py-3 px-3 text-right">
                  <button
                    type="button"
                    @click="saveProductStock(item)"
                    :disabled="savingStockId === item.id"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition disabled:opacity-50"
                  >
                    <Save class="w-3.5 h-3.5" />
                    <span>{{ savingStockId === item.id ? 'Saving...' : 'Update' }}</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
