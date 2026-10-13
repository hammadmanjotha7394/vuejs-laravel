<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { PackageSearch, Truck, CheckCircle2, Clock, ExternalLink, AlertCircle } from 'lucide-vue-next'

const orderNumber = ref('')
const email = ref('')
const loading = ref(false)
const error = ref('')
const order = ref(null)

async function handleTrack() {
  error.value = ''
  order.value = null
  loading.value = true
  try {
    const { data } = await axios.post('/api/orders/track', {
      order_number: orderNumber.value.trim(),
      email: email.value.trim(),
    })
    order.value = data.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Could not find an order matching those details.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="text-center max-w-xl mx-auto mb-10">
      <div class="w-12 h-12 rounded-full bg-[#F8F7F4] border border-[#EAE8E3] flex items-center justify-center mx-auto mb-4">
        <PackageSearch class="w-5 h-5 text-[#111111]" />
      </div>
      <h1 class="text-2xl sm:text-3xl font-semibold text-[#111111] tracking-tight">Track Your Order</h1>
      <p class="text-sm text-[#6B7280] mt-2">
        Enter the order number from your confirmation email along with your billing email address to check real-time fulfillment status.
      </p>
    </div>

    <!-- Lookup Card -->
    <form
      class="bg-white border border-[#EAE8E3] rounded-2xl p-6 sm:p-8 space-y-4 shadow-sm"
      @submit.prevent="handleTrack"
    >
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-[#111111] mb-1.5">Order Number</label>
          <input
            v-model="orderNumber"
            type="text"
            required
            placeholder="ORD-20261013-XXXXXX"
            class="w-full px-4 py-3 text-sm font-mono uppercase border border-[#EAE8E3] rounded-xl focus:outline-none focus:border-[#111111]"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-[#111111] mb-1.5">Email Address</label>
          <input
            v-model="email"
            type="email"
            required
            placeholder="you@example.com"
            class="w-full px-4 py-3 text-sm border border-[#EAE8E3] rounded-xl focus:outline-none focus:border-[#111111]"
          />
        </div>
      </div>

      <div v-if="error" class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-[#DC2626] flex items-center gap-2">
        <AlertCircle class="w-4 h-4 shrink-0" />
        <span>{{ error }}</span>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full py-3.5 rounded-full bg-[#111111] text-white text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50"
      >
        {{ loading ? 'Locating Order...' : 'Find Order Status' }}
      </button>
    </form>

    <!-- Order Result Card -->
    <div v-if="order" class="mt-8 bg-white border border-[#EAE8E3] rounded-2xl p-6 sm:p-8 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-[#EAE8E3]">
        <div>
          <div class="text-xs text-[#6B7280]">Order Reference</div>
          <h2 class="text-lg font-semibold text-[#111111] font-mono">{{ order.order_number }}</h2>
          <p class="text-xs text-[#6B7280] mt-0.5">
            Placed {{ order.placed_at ? new Date(order.placed_at).toLocaleDateString() : '' }}
            <span v-if="order.shipping_city">· Destination: {{ order.shipping_city }}, {{ order.shipping_country }}</span>
          </p>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-[#F8F7F4] border border-[#EAE8E3] text-[#111111]"
        >
          <CheckCircle2 class="w-3.5 h-3.5 text-[#15803D]" />
          {{ order.status }}
        </span>
      </div>

      <!-- Tracking Banner -->
      <div
        v-if="order.tracking_number"
        class="p-4 rounded-xl bg-[#F8F7F4] border border-[#EAE8E3] flex flex-col sm:flex-row sm:items-center justify-between gap-3"
      >
        <div class="flex items-center gap-3">
          <Truck class="w-5 h-5 text-[var(--brand)]" />
          <div>
            <div class="text-xs text-[#6B7280]">Carrier Tracking Number</div>
            <div class="text-sm font-mono font-semibold text-[#111111]">{{ order.tracking_number }}</div>
          </div>
        </div>
        <a
          v-if="order.tracking_url"
          :href="order.tracking_url"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#111111] text-white text-xs font-medium"
        >
          Track Carrier Package
          <ExternalLink class="w-3.5 h-3.5" />
        </a>
      </div>

      <!-- Items -->
      <div class="space-y-3">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-[#6B7280]">Order Items</h3>
        <div
          v-for="item in order.items"
          :key="item.id"
          class="flex items-center justify-between py-2.5 border-b border-[#F2F0EC] text-sm"
        >
          <div>
            <span class="font-medium text-[#111111]">{{ item.name }}</span>
            <span class="text-[#6B7280] ml-2">&times; {{ item.qty }}</span>
          </div>
          <span class="font-medium text-[#111111] tabular-nums">${{ Number(item.line_total).toFixed(2) }}</span>
        </div>
        <div class="flex items-center justify-between pt-2 text-base font-semibold text-[#111111]">
          <span>Order Total</span>
          <span class="tabular-nums">${{ Number(order.grand_total).toFixed(2) }}</span>
        </div>
      </div>

      <!-- Status History Timeline -->
      <div v-if="order.timeline?.length" class="pt-4 border-t border-[#EAE8E3] space-y-3">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-[#6B7280]">Fulfillment Timeline</h3>
        <div v-for="(step, idx) in order.timeline" :key="idx" class="flex items-start gap-3 text-xs">
          <Clock class="w-4 h-4 text-[#6B7280] shrink-0 mt-0.5" />
          <div>
            <span class="font-semibold uppercase text-[#111111]">{{ step.status }}</span>
            <span class="text-[#6B7280] ml-2">{{ step.created_at ? new Date(step.created_at).toLocaleString() : '' }}</span>
            <p class="text-[#6B7280] mt-0.5">{{ step.note }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
