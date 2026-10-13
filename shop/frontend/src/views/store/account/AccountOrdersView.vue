<script setup>
import { ref, onMounted } from 'vue'
import { useHead } from '@unhead/vue'
import apiClient from '@/api/client'
import { useSettingsStore } from '@/stores/settings'
import { Package, ArrowRight, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const settingsStore = useSettingsStore()

useHead({
  title: () => `My Orders | ${settingsStore.siteName}`,
})

const loading = ref(true)
const orders = ref([])
const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
})

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

const fetchOrders = async (page = 1) => {
  loading.value = true
  try {
    const res = await apiClient.get('/account/orders', { params: { page } })
    orders.value = res.data?.data || []
    meta.value = {
      current_page: res.data?.current_page || 1,
      last_page: res.data?.last_page || 1,
      total: res.data?.total || 0,
    }
  } catch (e) {
    orders.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchOrders(1)
})
</script>

<template>
  <div class="bg-white rounded-card border border-line p-5 sm:p-6 shadow-card">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-5 border-b border-line mb-6">
      <div>
        <h2 class="text-base sm:text-lg font-bold text-ink">Order History</h2>
        <p class="text-xs text-ink-muted">
          View order status, track shipments, download invoices, or buy items again
        </p>
      </div>
      <span v-if="!loading" class="text-xs font-semibold text-ink-muted">
        {{ meta.total }} {{ meta.total === 1 ? 'order' : 'orders' }} total
      </span>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-28 rounded-xl bg-surface animate-pulse" />
    </div>

    <div v-else-if="orders.length === 0" class="text-center py-12">
      <div class="w-12 h-12 rounded-full bg-surface text-ink-muted flex items-center justify-center mx-auto mb-3">
        <Package class="w-5 h-5" />
      </div>
      <p class="text-sm font-semibold text-ink">No orders found</p>
      <p class="text-xs text-ink-muted mt-1 mb-5">
        You haven't placed any orders with your account yet.
      </p>
      <RouterLink
        to="/shop"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
      >
        <span>Start Shopping</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </RouterLink>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="order in orders"
        :key="order.id"
        class="rounded-card border border-line p-4 sm:p-5 hover:border-brand/40 transition"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-line">
          <div class="flex flex-wrap items-center gap-3">
            <RouterLink
              :to="`/account/orders/${order.order_number}`"
              class="text-sm font-bold text-ink hover:text-brand font-mono transition"
            >
              {{ order.order_number }}
            </RouterLink>
            <span
              class="px-2.5 py-0.5 rounded-pill text-[11px] font-semibold border capitalize"
              :class="statusBadgeClass(order.status)"
            >
              {{ order.status }}
            </span>
            <span class="text-xs text-ink-muted">
              Placed on {{ order.placed_at }}
            </span>
          </div>

          <div class="flex items-center gap-3">
            <span class="text-sm font-bold text-ink">
              {{ settingsStore.formatPrice(order.grand_total) }}
            </span>
            <RouterLink
              :to="`/account/orders/${order.order_number}`"
              class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-pill bg-surface border border-line text-xs font-semibold text-ink hover:border-brand hover:text-brand transition"
            >
              <span>Order Details</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </RouterLink>
          </div>
        </div>

        <!-- Order Items Thumbnail Preview -->
        <div class="pt-4 flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-2.5 flex-wrap">
            <div
              v-for="item in order.items_preview"
              :key="item.id"
              class="flex items-center gap-2.5 bg-surface/60 border border-line rounded-xl pr-3 p-1.5"
            >
              <div class="w-10 h-10 rounded-lg bg-white border border-line overflow-hidden shrink-0 flex items-center justify-center">
                <img
                  v-if="item.image"
                  :src="item.image"
                  :alt="item.name"
                  class="w-full h-full object-cover"
                />
                <Package v-else class="w-4 h-4 text-ink-muted" />
              </div>
              <div class="max-w-[160px]">
                <p class="text-xs font-semibold text-ink truncate">{{ item.name }}</p>
                <p class="text-[11px] text-ink-muted">Qty: {{ item.qty }}</p>
              </div>
            </div>
          </div>

          <p class="text-xs text-ink-muted">
            {{ order.item_count }} {{ order.item_count === 1 ? 'item' : 'items' }} · Payment:
            <span class="font-semibold text-ink capitalize">{{ order.payment_status }}</span>
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="meta.last_page > 1"
        class="pt-4 flex items-center justify-between border-t border-line text-xs"
      >
        <span class="text-ink-muted">
          Page {{ meta.current_page }} of {{ meta.last_page }}
        </span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            :disabled="meta.current_page <= 1"
            @click="fetchOrders(meta.current_page - 1)"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-pill border border-line font-semibold disabled:opacity-40 hover:bg-surface transition"
          >
            <ChevronLeft class="w-3.5 h-3.5" />
            <span>Prev</span>
          </button>
          <button
            type="button"
            :disabled="meta.current_page >= meta.last_page"
            @click="fetchOrders(meta.current_page + 1)"
            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-pill border border-line font-semibold disabled:opacity-40 hover:bg-surface transition"
          >
            <span>Next</span>
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
