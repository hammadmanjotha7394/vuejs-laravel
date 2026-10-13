<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import {
  CircleDollarSign,
  TrendingUp,
  CreditCard,
  AlertCircle,
  RefreshCw,
  CheckCircle2,
  Download,
  RotateCcw,
  Truck,
  Receipt,
  Package,
  FolderTree,
  Landmark,
} from 'lucide-vue-next'
import apiClient from '@/api/client'

const financeData = ref(null)
const isLoading = ref(true)
const errorMessage = ref('')

const filters = reactive({
  period: 'day', // 'day' | 'week' | 'month'
  date_from: '',
  date_to: '',
})

const fetchFinance = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = {
      period: filters.period,
    }
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const res = await apiClient.get('/admin/finance/summary', { params })
    financeData.value = res.data
  } catch (err) {
    errorMessage.value =
      err.response?.data?.message || 'Access Denied: You do not have permission to view financial data.'
  } finally {
    isLoading.value = false
  }
}

const exportFinanceCsv = async () => {
  try {
    const res = await apiClient.get('/admin/finance/export-csv', {
      params: {
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
      },
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `finance-ledger-${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    errorMessage.value = 'Failed to export finance ledger CSV.'
  }
}

const maxSeriesRevenue = computed(() => {
  const series = financeData.value?.revenue_series || []
  if (!series.length) return 100
  return Math.max(10, ...series.map((s) => Number(s.revenue || 0)))
})

const maxTopProductRevenue = computed(() => {
  const items = financeData.value?.top_products || []
  if (!items.length) return 100
  return Math.max(10, ...items.map((p) => Number(p.revenue || 0)))
})

const maxTopCategoryRevenue = computed(() => {
  const items = financeData.value?.top_categories || []
  if (!items.length) return 100
  return Math.max(10, ...items.map((c) => Number(c.revenue || 0)))
})

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

watch(
  [() => filters.period, () => filters.date_from, () => filters.date_to],
  () => {
    fetchFinance()
  }
)

onMounted(() => {
  fetchFinance()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header & Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Finance & Revenue Analytics</h1>
        <p class="text-xs text-ink-muted mt-1">
          Revenue breakdown by day/week/month, average order value, refunds, tax & shipping collected, top revenue drivers, and cached Stripe payouts.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <!-- Period Switcher -->
        <div class="inline-flex items-center bg-surface border border-line rounded-pill p-1">
          <button
            v-for="opt in [
              { id: 'day', label: 'Daily (30d)' },
              { id: 'week', label: 'Weekly (12w)' },
              { id: 'month', label: 'Monthly (12m)' },
            ]"
            :key="opt.id"
            type="button"
            @click="filters.period = opt.id"
            class="px-3 py-1 rounded-pill text-xs font-semibold transition"
            :class="filters.period === opt.id ? 'bg-page text-ink shadow-sm' : 'text-ink-muted hover:text-ink'"
          >
            {{ opt.label }}
          </button>
        </div>

        <button
          type="button"
          @click="exportFinanceCsv"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:bg-surface transition"
        >
          <Download class="w-3.5 h-3.5 text-brand" />
          <span>Export CSV</span>
        </button>

        <button
          type="button"
          @click="fetchFinance"
          :disabled="isLoading"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-pill border border-line bg-page text-xs font-semibold text-ink hover:bg-surface transition disabled:opacity-50"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" />
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Error state -->
    <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-card p-5 text-error flex items-start justify-between gap-3">
      <div class="flex items-start gap-3">
        <AlertCircle class="w-5 h-5 flex-shrink-0 mt-0.5" />
        <div>
          <h2 class="text-sm font-semibold">Unable to Load Financial Report</h2>
          <p class="text-xs text-red-700 mt-1">{{ errorMessage }}</p>
        </div>
      </div>
      <button type="button" @click="errorMessage = ''" class="text-xs underline shrink-0">Dismiss</button>
    </div>

    <!-- Loading skeleton -->
    <div v-else-if="isLoading" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
      <div v-for="i in 6" :key="i" class="bg-page border border-line rounded-card p-5 animate-pulse">
        <div class="h-3 bg-gray-200 rounded w-1/2 mb-3"></div>
        <div class="h-7 bg-gray-200 rounded w-2/3"></div>
      </div>
    </div>

    <!-- Data Loaded -->
    <div v-else-if="financeData" class="space-y-6">
      <!-- 6 Financial KPI Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="bg-page border border-line rounded-card p-5 shadow-soft">
          <div class="flex items-center justify-between text-ink-muted mb-2">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Gross Revenue</span>
            <TrendingUp class="w-4 h-4 text-success" />
          </div>
          <div class="text-xl font-bold tracking-tight text-ink">
            ${{ formatMoney(financeData.revenue_total) }}
          </div>
          <p class="text-[11px] text-ink-muted mt-1">{{ financeData.orders_count }} paid orders</p>
        </div>

        <div class="bg-page border border-line rounded-card p-5 shadow-soft">
          <div class="flex items-center justify-between text-ink-muted mb-2">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Net Revenue</span>
            <CircleDollarSign class="w-4 h-4 text-brand" />
          </div>
          <div class="text-xl font-bold tracking-tight text-ink">
            ${{ formatMoney(financeData.net_revenue ?? financeData.revenue_total) }}
          </div>
          <p class="text-[11px] text-ink-muted mt-1">After refunds</p>
        </div>

        <div class="bg-page border border-line rounded-card p-5 shadow-soft">
          <div class="flex items-center justify-between text-ink-muted mb-2">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Avg Order Value</span>
            <CreditCard class="w-4 h-4 text-brand" />
          </div>
          <div class="text-xl font-bold tracking-tight text-ink">
            ${{ formatMoney(financeData.average_order_value) }}
          </div>
          <p class="text-[11px] text-ink-muted mt-1">Per paid checkout</p>
        </div>

        <div class="bg-page border border-line rounded-card p-5 shadow-soft">
          <div class="flex items-center justify-between text-ink-muted mb-2">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Refunds Total</span>
            <RotateCcw class="w-4 h-4 text-error" />
          </div>
          <div class="text-xl font-bold tracking-tight text-error">
            ${{ formatMoney(financeData.refunds_total) }}
          </div>
          <p class="text-[11px] text-ink-muted mt-1">{{ financeData.refunds_count || 0 }} refunded orders</p>
        </div>

        <div class="bg-page border border-line rounded-card p-5 shadow-soft">
          <div class="flex items-center justify-between text-ink-muted mb-2">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Tax Collected</span>
            <Receipt class="w-4 h-4 text-amber-600" />
          </div>
          <div class="text-xl font-bold tracking-tight text-ink">
            ${{ formatMoney(financeData.tax_collected) }}
          </div>
          <p class="text-[11px] text-ink-muted mt-1">Sales tax total</p>
        </div>

        <div class="bg-page border border-line rounded-card p-5 shadow-soft">
          <div class="flex items-center justify-between text-ink-muted mb-2">
            <span class="text-[11px] font-semibold uppercase tracking-wider">Shipping Collected</span>
            <Truck class="w-4 h-4 text-indigo-600" />
          </div>
          <div class="text-xl font-bold tracking-tight text-ink">
            ${{ formatMoney(financeData.shipping_collected) }}
          </div>
          <p class="text-[11px] text-ink-muted mt-1">Delivery fees</p>
        </div>
      </div>

      <!-- Revenue Bar Chart (Day / Week / Month) -->
      <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div>
            <h2 class="text-sm font-bold text-ink">
              Revenue Trend ({{ filters.period === 'day' ? 'Last 30 Days' : filters.period === 'week' ? 'Last 12 Weeks' : 'Last 12 Months' }})
            </h2>
            <p class="text-xs text-ink-muted">Hover over any bar to inspect revenue, orders count, tax, and shipping.</p>
          </div>

          <div class="flex items-center gap-2 text-xs">
            <input
              v-model="filters.date_from"
              type="date"
              class="px-2.5 py-1.5 bg-surface border border-line rounded-lg text-xs text-ink"
            />
            <span class="text-ink-muted">to</span>
            <input
              v-model="filters.date_to"
              type="date"
              class="px-2.5 py-1.5 bg-surface border border-line rounded-lg text-xs text-ink"
            />
          </div>
        </div>

        <div v-if="financeData.revenue_series?.length" class="h-52 flex items-end gap-1.5 pt-6 border-b border-line pb-2 overflow-x-auto">
          <div
            v-for="point in financeData.revenue_series"
            :key="point.date"
            class="flex-1 min-w-[20px] flex flex-col items-center group relative h-full justify-end"
          >
            <!-- Tooltip -->
            <div
              class="opacity-0 group-hover:opacity-100 pointer-events-none transition bg-ink text-white text-[10px] rounded-lg px-2.5 py-1.5 absolute -top-2 z-10 whitespace-nowrap shadow-md"
            >
              <p class="font-bold">{{ point.label }}: ${{ formatMoney(point.revenue) }}</p>
              <p>{{ point.orders_count }} order(s) · Tax: ${{ formatMoney(point.tax) }}</p>
            </div>

            <div
              class="w-full rounded-t-md bg-brand/85 group-hover:bg-brand transition-all duration-200"
              :style="{
                height: `${Math.max(4, Math.round((Number(point.revenue) / maxSeriesRevenue) * 150))}px`,
              }"
            ></div>
            <span class="text-[9px] text-ink-muted mt-1.5 truncate max-w-full">{{ point.label }}</span>
          </div>
        </div>
      </div>

      <!-- Top Products & Top Categories by Revenue -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Products -->
        <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-4">
          <h2 class="text-sm font-bold text-ink flex items-center gap-2">
            <Package class="w-4 h-4 text-brand" />
            <span>Top Products by Revenue</span>
          </h2>

          <div v-if="!financeData.top_products?.length" class="text-xs text-ink-muted py-6 text-center">
            No paid product sales recorded in this period.
          </div>

          <div v-else class="space-y-3.5">
            <div v-for="prod in financeData.top_products" :key="prod.product_id || prod.name" class="space-y-1">
              <div class="flex items-center justify-between text-xs">
                <div class="min-w-0 truncate pr-3">
                  <span class="font-semibold text-ink">{{ prod.name }}</span>
                  <span v-if="prod.sku" class="text-[11px] font-mono text-ink-muted ml-1.5">({{ prod.sku }})</span>
                </div>
                <div class="text-right shrink-0">
                  <span class="font-bold text-ink">${{ formatMoney(prod.revenue) }}</span>
                  <span class="text-[11px] text-ink-muted ml-1.5">· {{ prod.units_sold }} sold</span>
                </div>
              </div>
              <div class="w-full h-1.5 bg-surface rounded-full overflow-hidden">
                <div
                  class="h-full bg-brand rounded-full"
                  :style="{ width: `${Math.min(100, Math.round((prod.revenue / maxTopProductRevenue) * 100))}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Categories -->
        <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-4">
          <h2 class="text-sm font-bold text-ink flex items-center gap-2">
            <FolderTree class="w-4 h-4 text-brand" />
            <span>Top Categories by Revenue</span>
          </h2>

          <div v-if="!financeData.top_categories?.length" class="text-xs text-ink-muted py-6 text-center">
            No category revenue recorded in this period.
          </div>

          <div v-else class="space-y-3.5">
            <div v-for="cat in financeData.top_categories" :key="cat.category_id" class="space-y-1">
              <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-ink">{{ cat.name }}</span>
                <div class="text-right shrink-0">
                  <span class="font-bold text-ink">${{ formatMoney(cat.revenue) }}</span>
                  <span class="text-[11px] text-ink-muted ml-1.5">· {{ cat.units_sold }} units</span>
                </div>
              </div>
              <div class="w-full h-1.5 bg-surface rounded-full overflow-hidden">
                <div
                  class="h-full bg-brand rounded-full"
                  :style="{ width: `${Math.min(100, Math.round((cat.revenue / maxTopCategoryRevenue) * 100))}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stripe Balance & Recent Payouts -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Stripe Balance Card -->
        <div class="bg-page border border-line rounded-card p-6 shadow-soft space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-ink flex items-center gap-2">
              <Landmark class="w-4 h-4 text-brand" />
              <span>Stripe Balance</span>
            </h2>
            <span
              class="px-2.5 py-0.5 rounded-pill text-[10px] font-bold uppercase border"
              :class="
                financeData.stripe_balance?.mode === 'live'
                  ? 'bg-emerald-50 text-success border-emerald-200'
                  : 'bg-amber-50 text-amber-800 border-amber-200'
              "
            >
              {{ financeData.stripe_balance?.mode || 'test' }} mode
            </span>
          </div>

          <div class="space-y-3">
            <div class="bg-surface border border-line rounded-xl p-4">
              <span class="text-[11px] text-ink-muted block">Available for Payout</span>
              <span class="text-xl font-bold text-ink">
                ${{ formatMoney(financeData.stripe_balance?.available) }}
              </span>
            </div>

            <div class="bg-surface border border-line rounded-xl p-4">
              <span class="text-[11px] text-ink-muted block">Pending Settlement</span>
              <span class="text-lg font-bold text-ink-muted">
                ${{ formatMoney(financeData.stripe_balance?.pending) }}
              </span>
            </div>
          </div>

          <p class="text-[11px] text-ink-muted">
            Balances and payout records are synced from Stripe and cached for 10 minutes.
          </p>
        </div>

        <!-- Recent Payouts Table -->
        <div class="lg:col-span-2 bg-page border border-line rounded-card p-6 shadow-soft">
          <h2 class="text-sm font-bold text-ink mb-4">Recent Stripe Payouts</h2>
          <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
              <thead class="border-b border-line text-ink-muted font-semibold uppercase tracking-wider">
                <tr>
                  <th class="py-2.5 px-3">Payout ID</th>
                  <th class="py-2.5 px-3">Amount</th>
                  <th class="py-2.5 px-3">Method</th>
                  <th class="py-2.5 px-3">Status</th>
                  <th class="py-2.5 px-3">Arrival Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-line">
                <tr
                  v-for="payout in financeData.recent_payouts"
                  :key="payout.id"
                  class="hover:bg-surface transition"
                >
                  <td class="py-3 px-3 font-mono font-medium text-ink">{{ payout.id }}</td>
                  <td class="py-3 px-3 font-bold text-ink">${{ formatMoney(payout.amount) }}</td>
                  <td class="py-3 px-3 capitalize text-ink-muted">{{ payout.method || 'standard' }}</td>
                  <td class="py-3 px-3">
                    <span
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-pill text-[11px] font-semibold bg-emerald-50 text-success border border-emerald-200"
                    >
                      <CheckCircle2 class="w-3 h-3" />
                      <span>{{ payout.status }}</span>
                    </span>
                  </td>
                  <td class="py-3 px-3 text-ink-muted">{{ payout.date }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
