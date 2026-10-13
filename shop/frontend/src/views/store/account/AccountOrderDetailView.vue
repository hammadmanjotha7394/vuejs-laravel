<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '@unhead/vue'
import apiClient from '@/api/client'
import { useSettingsStore } from '@/stores/settings'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import {
  ArrowLeft,
  Printer,
  RefreshCw,
  XCircle,
  Truck,
  ExternalLink,
  Package,
  MapPin,
  Clock,
  AlertTriangle,
  CheckCircle2,
} from 'lucide-vue-next'

const route = useRoute()
const settingsStore = useSettingsStore()
const cartStore = useCartStore()
const toast = useToastStore()

const orderNumber = route.params.number
const loading = ref(true)
const notFound = ref(false)
const order = ref(null)
const buyingAgain = ref(false)

// Cancellation Modal State
const cancelModalOpen = ref(false)
const cancelReason = ref('')
const cancelSubmitting = ref(false)
const cancelError = ref('')

useHead({
  title: () => `Order ${orderNumber} | ${settingsStore.siteName}`,
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

const fetchOrder = async () => {
  loading.value = true
  notFound.value = false
  try {
    const res = await apiClient.get(`/account/orders/${orderNumber}`)
    order.value = res.data?.data || null
  } catch (e) {
    notFound.value = true
    order.value = null
  } finally {
    loading.value = false
  }
}

const handlePrintInvoice = () => {
  window.print()
}

const handleBuyAgain = async () => {
  if (!order.value?.items?.length) return
  buyingAgain.value = true
  let addedCount = 0

  try {
    for (const item of order.value.items) {
      if (item.product_id) {
        try {
          await cartStore.addItem({
            productId: item.product_id,
            variantId: item.variant_id || null,
            qty: item.qty || 1,
            openDrawer: false,
          })
          addedCount++
        } catch (err) {
          // Continue adding available items if one is out of stock
        }
      }
    }

    if (addedCount > 0) {
      toast.success(`Added ${addedCount} ${addedCount === 1 ? 'item' : 'items'} from ${order.value.order_number} to your bag.`)
      cartStore.openDrawer()
    } else {
      toast.error('Items from this order are currently unavailable.')
    }
  } finally {
    buyingAgain.value = false
  }
}

const submitCancelRequest = async () => {
  cancelError.value = ''
  if (!cancelReason.value.trim()) {
    cancelError.value = 'Please provide a brief reason for cancellation.'
    return
  }

  cancelSubmitting.value = true
  try {
    const res = await apiClient.post(`/account/orders/${order.value.order_number}/cancel-request`, {
      reason: cancelReason.value.trim(),
    })
    order.value = res.data?.data || order.value
    toast.success(res.data?.message || 'Cancellation request submitted.')
    cancelModalOpen.value = false
    cancelReason.value = ''
  } catch (err) {
    cancelError.value =
      err.response?.data?.errors?.order?.[0] ||
      err.response?.data?.errors?.reason?.[0] ||
      err.response?.data?.message ||
      'Unable to submit cancellation request.'
  } finally {
    cancelSubmitting.value = false
  }
}

onMounted(() => {
  fetchOrder()
})
</script>

<template>
  <div>
    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-card border border-line p-6 space-y-4">
      <div class="h-8 w-48 bg-surface rounded animate-pulse" />
      <div class="h-36 bg-surface rounded-xl animate-pulse" />
      <div class="h-48 bg-surface rounded-xl animate-pulse" />
    </div>

    <!-- 404 / Unauthorized Order State -->
    <div
      v-else-if="notFound || !order"
      class="bg-white rounded-card border border-line p-8 sm:p-12 text-center shadow-card"
    >
      <div class="w-12 h-12 rounded-full bg-red-50 text-error flex items-center justify-center mx-auto mb-3">
        <AlertTriangle class="w-6 h-6" />
      </div>
      <h2 class="text-lg font-bold text-ink">Order Not Found</h2>
      <p class="text-xs text-ink-muted max-w-md mx-auto mt-1 mb-6">
        We couldn't find order <span class="font-mono font-semibold text-ink">{{ orderNumber }}</span> associated with your account.
      </p>
      <RouterLink
        to="/account/orders"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark transition"
      >
        <ArrowLeft class="w-3.5 h-3.5" />
        <span>Back to My Orders</span>
      </RouterLink>
    </div>

    <!-- Order Details & Printable Invoice View -->
    <div v-else class="space-y-6">
      <!-- Top Action Bar (Hidden on Print) -->
      <div class="print:hidden flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-card border border-line p-4 sm:p-5 shadow-card">
        <div class="flex items-center gap-3">
          <RouterLink
            to="/account/orders"
            class="p-2 rounded-full border border-line text-ink-muted hover:text-ink hover:bg-surface transition"
            aria-label="Back to orders"
          >
            <ArrowLeft class="w-4 h-4" />
          </RouterLink>
          <div>
            <div class="flex items-center gap-2.5 flex-wrap">
              <h2 class="text-base sm:text-lg font-bold text-ink font-mono">
                {{ order.order_number }}
              </h2>
              <span
                class="px-2.5 py-0.5 rounded-pill text-[11px] font-semibold border capitalize"
                :class="statusBadgeClass(order.status)"
              >
                {{ order.status }}
              </span>
            </div>
            <p class="text-xs text-ink-muted mt-0.5">
              Placed on {{ order.placed_at }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Buy Again Button -->
          <button
            type="button"
            :disabled="buyingAgain"
            @click="handleBuyAgain"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-brand text-white text-xs font-semibold hover:bg-brand-dark disabled:opacity-50 transition"
          >
            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': buyingAgain }" />
            <span>{{ buyingAgain ? 'Adding...' : 'Buy Again' }}</span>
          </button>

          <!-- Print / Download Invoice Button -->
          <button
            type="button"
            @click="handlePrintInvoice"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill border border-line bg-white text-ink text-xs font-semibold hover:border-brand hover:text-brand transition"
          >
            <Printer class="w-3.5 h-3.5" />
            <span>Print / Download Invoice</span>
          </button>

          <!-- Request Cancellation Button (For unshipped orders) -->
          <button
            v-if="order.can_request_cancel"
            type="button"
            @click="cancelModalOpen = true"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill border border-red-200 bg-red-50/60 text-error text-xs font-semibold hover:bg-red-100 transition"
          >
            <XCircle class="w-3.5 h-3.5" />
            <span>Request Cancellation</span>
          </button>
        </div>
      </div>

      <!-- Cancellation Request Submitted Banner -->
      <div
        v-if="order.has_cancel_request && order.status !== 'cancelled'"
        class="print:hidden bg-amber-50 border border-amber-200 rounded-card p-4 flex items-start gap-3 text-xs text-amber-900"
      >
        <Clock class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" />
        <div>
          <p class="font-bold">Cancellation Request Pending Review</p>
          <p class="text-amber-800 mt-0.5">
            Your cancellation request has been sent to our fulfillment team. We will notify you as soon as it is processed.
          </p>
        </div>
      </div>

      <!-- Tracking Banner (If Shipped / Tracking Available) -->
      <div
        v-if="order.tracking_number"
        class="print:hidden bg-indigo-50/70 border border-indigo-200 rounded-card p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
            <Truck class="w-5 h-5" />
          </div>
          <div>
            <p class="text-xs font-bold text-indigo-950">Shipment Tracking Number</p>
            <p class="text-sm font-mono font-semibold text-indigo-800">{{ order.tracking_number }}</p>
          </div>
        </div>
        <a
          v-if="order.tracking_url"
          :href="order.tracking_url"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1.5 px-4 py-2 rounded-pill bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition self-start sm:self-auto"
        >
          <span>Track Package</span>
          <ExternalLink class="w-3.5 h-3.5" />
        </a>
      </div>

      <!-- Printable Invoice Card Container -->
      <div class="bg-white rounded-card border border-line p-6 sm:p-8 shadow-card print:shadow-none print:border-none print:p-0">
        <!-- Print-Only Invoice Header -->
        <div class="hidden print:flex items-start justify-between pb-6 mb-6 border-b border-line">
          <div>
            <h1 class="text-2xl font-bold text-ink">{{ settingsStore.siteName }}</h1>
            <p v-if="settingsStore.settings?.tagline" class="text-xs text-ink-muted mt-0.5">
              {{ settingsStore.settings.tagline }}
            </p>
            <p v-if="settingsStore.settings?.support_email" class="text-xs text-ink-muted mt-1">
              {{ settingsStore.settings.support_email }}
            </p>
          </div>
          <div class="text-right">
            <p class="text-xs font-bold uppercase tracking-wider text-ink-muted">Official Tax Invoice</p>
            <p class="text-lg font-bold font-mono text-ink mt-0.5">{{ order.order_number }}</p>
            <p class="text-xs text-ink-muted mt-0.5">Date: {{ order.placed_at }}</p>
            <p class="text-xs text-ink-muted capitalize">
              Order Status: {{ order.status }} · Payment: {{ order.payment_status }}
            </p>
          </div>
        </div>

        <!-- Order Items Table -->
        <h3 class="text-sm font-bold text-ink mb-4">Items Ordered</h3>
        <div class="divide-y divide-line border-t border-b border-line">
          <div
            v-for="item in order.items"
            :key="item.id"
            class="py-4 flex items-center justify-between gap-4"
          >
            <div class="flex items-center gap-3.5 min-w-0">
              <div class="w-14 h-14 rounded-xl bg-surface border border-line overflow-hidden shrink-0 flex items-center justify-center print:hidden">
                <img
                  v-if="item.image"
                  :src="item.image"
                  :alt="item.name"
                  class="w-full h-full object-cover"
                />
                <Package v-else class="w-5 h-5 text-ink-muted" />
              </div>
              <div class="min-w-0">
                <RouterLink
                  v-if="item.slug"
                  :to="`/products/${item.slug}`"
                  class="text-sm font-semibold text-ink hover:text-brand transition block truncate"
                >
                  {{ item.name }}
                </RouterLink>
                <p v-else class="text-sm font-semibold text-ink truncate">
                  {{ item.name }}
                </p>
                <p class="text-xs text-ink-muted mt-0.5">
                  SKU: <span class="font-mono">{{ item.sku }}</span> ·
                  {{ settingsStore.formatPrice(item.unit_price) }} × {{ item.qty }}
                </p>
              </div>
            </div>

            <div class="text-right shrink-0">
              <p class="text-sm font-bold text-ink">
                {{ settingsStore.formatPrice(item.line_total) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Addresses & Order Totals Grid -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pt-6">
          <!-- Shipping & Billing Addresses -->
          <div class="md:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
            <div v-if="order.shipping_address">
              <h4 class="font-bold text-ink flex items-center gap-1.5 mb-2">
                <MapPin class="w-3.5 h-3.5 text-brand print:hidden" />
                <span>Shipping Address</span>
              </h4>
              <div class="text-ink-muted space-y-0.5">
                <p class="font-semibold text-ink">{{ order.shipping_address.name }}</p>
                <p>{{ order.shipping_address.line1 }}</p>
                <p v-if="order.shipping_address.line2">{{ order.shipping_address.line2 }}</p>
                <p>
                  {{ order.shipping_address.city }},
                  {{ order.shipping_address.state }}
                  {{ order.shipping_address.postal_code }}
                </p>
                <p>{{ order.shipping_address.country }}</p>
                <p v-if="order.shipping_address.phone" class="pt-1">
                  Phone: {{ order.shipping_address.phone }}
                </p>
              </div>
            </div>

            <div v-if="order.billing_address">
              <h4 class="font-bold text-ink flex items-center gap-1.5 mb-2">
                <MapPin class="w-3.5 h-3.5 text-brand print:hidden" />
                <span>Billing Address</span>
              </h4>
              <div class="text-ink-muted space-y-0.5">
                <p class="font-semibold text-ink">{{ order.billing_address.name }}</p>
                <p>{{ order.billing_address.line1 }}</p>
                <p v-if="order.billing_address.line2">{{ order.billing_address.line2 }}</p>
                <p>
                  {{ order.billing_address.city }},
                  {{ order.billing_address.state }}
                  {{ order.billing_address.postal_code }}
                </p>
                <p>{{ order.billing_address.country }}</p>
              </div>
            </div>
          </div>

          <!-- Financial Summary -->
          <div class="md:col-span-5 bg-surface/60 rounded-xl p-4 sm:p-5 border border-line space-y-2.5 text-xs">
            <div class="flex items-center justify-between text-ink-muted">
              <span>Subtotal</span>
              <span class="font-semibold text-ink">{{ settingsStore.formatPrice(order.subtotal) }}</span>
            </div>

            <div v-if="order.discount_total > 0" class="flex items-center justify-between text-emerald-700">
              <span>
                Discount
                <span v-if="order.coupon_code" class="font-mono text-[11px]">({{ order.coupon_code }})</span>
              </span>
              <span class="font-semibold">-{{ settingsStore.formatPrice(order.discount_total) }}</span>
            </div>

            <div class="flex items-center justify-between text-ink-muted">
              <span>Shipping</span>
              <span class="font-semibold text-ink">
                {{ order.shipping_total > 0 ? settingsStore.formatPrice(order.shipping_total) : 'Free' }}
              </span>
            </div>

            <div class="flex items-center justify-between text-ink-muted">
              <span>Estimated Tax</span>
              <span class="font-semibold text-ink">{{ settingsStore.formatPrice(order.tax_total) }}</span>
            </div>

            <div class="pt-3 border-t border-line flex items-center justify-between text-sm font-bold text-ink">
              <span>Grand Total ({{ order.currency }})</span>
              <span class="text-base">{{ settingsStore.formatPrice(order.grand_total) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Status Timeline History (Hidden on Print) -->
      <div
        v-if="order.status_history?.length"
        class="print:hidden bg-white rounded-card border border-line p-5 sm:p-6 shadow-card"
      >
        <h3 class="text-sm font-bold text-ink mb-4 flex items-center gap-2">
          <Clock class="w-4 h-4 text-brand" />
          <span>Order Status Timeline</span>
        </h3>
        <div class="space-y-4">
          <div
            v-for="entry in order.status_history"
            :key="entry.id"
            class="flex items-start gap-3 text-xs"
          >
            <CheckCircle2 class="w-4 h-4 text-brand shrink-0 mt-0.5" />
            <div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-ink capitalize">{{ entry.status }}</span>
                <span class="text-ink-muted">· {{ entry.created_at }}</span>
              </div>
              <p v-if="entry.note" class="text-ink-muted mt-0.5">{{ entry.note }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Request Cancellation Modal -->
    <div
      v-if="cancelModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4 print:hidden"
    >
      <div class="bg-white rounded-card border border-line max-w-md w-full p-6 shadow-xl">
        <div class="flex items-center justify-between pb-3 border-b border-line mb-4">
          <h3 class="text-base font-bold text-ink">Request Order Cancellation</h3>
          <button
            type="button"
            @click="cancelModalOpen = false"
            class="text-ink-muted hover:text-ink text-xs font-semibold"
          >
            Close
          </button>
        </div>

        <p class="text-xs text-ink-muted mb-4">
          Please let us know why you would like to cancel order
          <span class="font-mono font-bold text-ink">{{ order?.order_number }}</span>.
        </p>

        <form @submit.prevent="submitCancelRequest" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-ink mb-1.5">
              Cancellation Reason *
            </label>
            <textarea
              v-model="cancelReason"
              rows="3"
              required
              placeholder="e.g., Ordered the wrong item or need to change shipping address..."
              class="w-full rounded-xl border border-line px-3.5 py-2.5 text-xs text-ink focus:outline-none focus:border-brand"
            />
          </div>

          <p v-if="cancelError" class="text-xs text-error font-medium">
            {{ cancelError }}
          </p>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              @click="cancelModalOpen = false"
              class="px-4 py-2 rounded-pill border border-line text-xs font-semibold text-ink-muted hover:text-ink"
            >
              Keep Order
            </button>
            <button
              type="submit"
              :disabled="cancelSubmitting"
              class="px-5 py-2 rounded-pill bg-error text-white text-xs font-semibold hover:bg-red-700 disabled:opacity-50 transition"
            >
              {{ cancelSubmitting ? 'Submitting...' : 'Submit Request' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
