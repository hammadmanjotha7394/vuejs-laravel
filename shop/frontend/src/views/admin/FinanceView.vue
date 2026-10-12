<script setup>
import { ref, onMounted } from 'vue'
import { CircleDollarSign, TrendingUp, CreditCard, AlertCircle, RefreshCw, CheckCircle2 } from 'lucide-vue-next'
import apiClient from '@/api/client'

const financeData = ref(null)
const isLoading = ref(true)
const errorMessage = ref('')

const fetchFinance = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await apiClient.get('/admin/finance/summary')
    financeData.value = res.data
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Access Denied: You do not have permission to view financial data.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchFinance()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-ink">Finance & Revenue</h1>
        <p class="text-xs text-ink-muted mt-1">Financial metrics, payout history, and volume analytics</p>
      </div>

      <button
        @click="fetchFinance"
        :disabled="isLoading"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-pill border border-line bg-page text-xs font-medium text-ink hover:bg-surface transition disabled:opacity-50"
      >
        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" />
        <span>Refresh</span>
      </button>
    </div>

    <!-- Error state / 403 response -->
    <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-card p-6 text-error">
      <div class="flex items-start gap-3">
        <AlertCircle class="w-5 h-5 flex-shrink-0 mt-0.5" />
        <div>
          <h2 class="text-sm font-semibold">Forbidden (403): Insufficient Permissions</h2>
          <p class="text-xs text-red-700 mt-1">{{ errorMessage }}</p>
        </div>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-else-if="isLoading" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div v-for="i in 3" :key="i" class="bg-page border border-line rounded-card p-6 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-1/3 mb-3"></div>
        <div class="h-8 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Data Loaded -->
    <div v-else-if="financeData" class="space-y-6">
      <!-- Metric Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-page border border-line rounded-card p-6 shadow-soft">
          <div class="flex items-center justify-between text-ink-muted mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider">Gross Revenue</span>
            <TrendingUp class="w-4 h-4 text-success" />
          </div>
          <div class="text-2xl font-bold tracking-tight text-ink">
            ${{ Number(financeData.revenue_total).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
          </div>
          <p class="text-[11px] text-ink-muted mt-1">Confirmed orders to date</p>
        </div>

        <div class="bg-page border border-line rounded-card p-6 shadow-soft">
          <div class="flex items-center justify-between text-ink-muted mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider">Paid Orders</span>
            <CircleDollarSign class="w-4 h-4 text-brand" />
          </div>
          <div class="text-2xl font-bold tracking-tight text-ink">
            {{ financeData.orders_count }}
          </div>
          <p class="text-[11px] text-ink-muted mt-1">Completed transactions</p>
        </div>

        <div class="bg-page border border-line rounded-card p-6 shadow-soft">
          <div class="flex items-center justify-between text-ink-muted mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider">Avg Order Value</span>
            <CreditCard class="w-4 h-4 text-accent" />
          </div>
          <div class="text-2xl font-bold tracking-tight text-ink">
            ${{ Number(financeData.average_order_value).toFixed(2) }}
          </div>
          <p class="text-[11px] text-ink-muted mt-1">Average cart ticket</p>
        </div>
      </div>

      <!-- Recent Payouts Table -->
      <div class="bg-page border border-line rounded-card p-6 shadow-soft">
        <h2 class="text-sm font-semibold text-ink mb-4">Recent Stripe Payouts</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left">
            <thead class="border-b border-line text-ink-muted font-semibold uppercase tracking-wider">
              <tr>
                <th class="py-2.5 px-3">Payout ID</th>
                <th class="py-2.5 px-3">Amount</th>
                <th class="py-2.5 px-3">Status</th>
                <th class="py-2.5 px-3">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-line">
              <tr v-for="payout in financeData.recent_payouts" :key="payout.id" class="hover:bg-surface transition">
                <td class="py-3 px-3 font-mono font-medium text-ink">{{ payout.id }}</td>
                <td class="py-3 px-3 font-semibold text-ink">${{ payout.amount.toFixed(2) }}</td>
                <td class="py-3 px-3">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-pill text-[11px] font-semibold bg-emerald-50 text-success border border-emerald-200">
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
</template>
